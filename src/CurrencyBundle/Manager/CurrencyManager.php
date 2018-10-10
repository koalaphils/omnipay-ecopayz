<?php

namespace CurrencyBundle\Manager;

use AppBundle\Event\GenericEntityEvent;
use AppBundle\Exceptions\FormValidationException;
use AppBundle\Manager\AbstractManager;
use AppBundle\Manager\SettingManager;
use DateTimeInterface;
use DbBundle\Entity\Currency;
use DbBundle\Entity\CurrencyRate;
use DbBundle\Entity\User;
use DbBundle\Listener\VersionableListener;
use DbBundle\Repository\CurrencyRateRepository;
use DbBundle\Repository\CurrencyRepository;
use DbBundle\Repository\UserRepository;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class CurrencyManager extends AbstractManager
{
    public function handleForm(Form $form, Request $request)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $currency = $form->getData();
            $originalCurrency = $this->getUnitOfWork()->getOriginalEntityData($currency);
            if (empty($originalCurrency)) {
                $originalCurrency['rate'] = $currency->getRate();
            }
            $this->getRepository()->save($currency);
            $this->saveCurrencyRate($currency, $originalCurrency);

            return $currency;
        }

        throw new FormValidationException($form);
    }

    public function getCurrencyList($filters = []): array
    {
        $results = [];

        if (array_get($filters, 'datatable', 0)) {
            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }
            $orders = (!array_has($filters, 'order')) ? [['column' => 'c.createdAt', 'dir' => 'DESC']] : $filters['order'];

            $results['data'] = $this->getRepository()->getCurrencyList($filters, $orders);
            if (array_get($filters, 'route', 0)) {
                $results['data'] = array_map(function ($data) {
                    $data['currency'] = $data;
                    $data['routes'] = [
                        'update' => $this->getRouter()->generate('currency.update_page', ['id' => $data['currency']['id']]),
                        'view' => $this->getRouter()->generate('currency.view_page', ['id' => $data['currency']['id']]),
                        'save' => $this->getRouter()->generate('currency.save', ['id' => $data['currency']['id']]),
                    ];
                    $data['ratesHistory'] = $this->getTopTenCurrencyRateHistoryByCurrencyId($data['currency']['id']);

                    return $data;
                }, $results['data']);
            }
            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getCurrencyListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getCurrencyListAllCount();
        } elseif (array_get($filters, 'select2', 0)) {
            $results['items'] = array_map(function ($group) use ($filters) {
                return [
                    'id' => $group[array_get($filters, 'idColumn', 'id')],
                    'text' => $group['name'],
                ];
            }, $this->getRepository()->getCurrencyList($filters));
            $results['recordsFiltered'] = $this->getRepository()->getCurrencyListFilterCount($filters);
        } else {
            $results = $this->getRepository()->getCurrencyList($filters);
        }

        return $results;
    }

    public function getBaseCurrency(): Currency
    {
        return $this->getRepository()->find($this->getSettingManager()->getSetting('currency.base'));
    }

    public function fixCurrencyRateDependsOnDate(Currency $currency, DateTimeInterface $date): void
    {
        $currencyRate = $this->getCurrencyRateRepository()->findRateOfCurrencyBeforeOrOnDate($currency, $date);
        if ($currencyRate instanceof CurrencyRate) {
            $currency->setRate($currencyRate->getRate());
        }
    }

    private function getTopTenCurrencyRateHistoryByCurrencyId(int $currencyId): array
    {
        $numberToDisplay = CurrencyRate::DEFAULT_NUMBER_TO_DISPLAY_HISTORY;

        return $this->getCurrencyRateRepository()->findCurrencyRateHistoryByCurrencyId($currencyId, $numberToDisplay);
    }

    private function saveCurrencyRate(Currency $currency, array $originalCurrency = []): void
    {
        $currencyRate = $this->getCurrencyRateRepository()->findLatestRate($currency);
        $user = $this->getUserRepository()->find($this->getUser()->getId());
        
        if (!($currencyRate instanceof CurrencyRate)) {
            $currencyRate = new CurrencyRate();
            $currencyRate->setSourceCurrency($currency);
            $currencyRate->setDestinationCurrency($this->getBaseCurrency());
            $currencyRate->setRate($originalCurrency['rate']);
            $currencyRate->setDestinationRate(1);
            $currencyRate->preserveOriginal();
            $currencyRate->setCreator($user);
            $currencyRate->setCreatedAt($currency->getCreatedAt());
            $this->getEventDispatcher()->dispatch(VersionableListener::VERSIONABLE_SAVE, new GenericEntityEvent($currencyRate));
        }
        
        if (!number_are_equal($currencyRate->getRate(), $currency->getRate())
            || !$this->getBaseCurrency()->isEqualTo($currencyRate->getDestinationCurrency())
        ) {
            $currencyRate->preserveOriginal();
            $currencyRate->setDestinationCurrency($this->getBaseCurrency());
            $currencyRate->setRate($currency->getRate());
            $currencyRate->setCreator($user);

            $this->getEventDispatcher()->dispatch(VersionableListener::VERSIONABLE_SAVE, new GenericEntityEvent($currencyRate));
        }
    }
    
    public function getConvertionRate(Currency $sourceCurrency, Currency $destinationCurrency, DateTimeInterface $date): CurrencyRate
    {
        
        $currencyRate = new CurrencyRate();
        $currencyRate->setSourceCurrency($sourceCurrency);
        $currencyRate->setDestinationCurrency($destinationCurrency);
        
        if ($sourceCurrency->isEqualTo($destinationCurrency)) {
            $currencyRate->setRate(1);
            $currencyRate->setDestinationRate(1);
            
            return $currencyRate;
        }
        
        $currentCurrency = $sourceCurrency;
        $currentCurrencyRate = $this->getCurrencyRateDependsOnDate($currentCurrency, $date);
       
        if ($currentCurrencyRate->getDestinationCurrency()->isEqualTo($currentCurrencyRate->getSourceCurrency())) {
            $currentCurrency = $destinationCurrency;
            $currentCurrencyRate = $this->getCurrencyRateDependsOnDate($currentCurrency, $date);
            $rate = $currentCurrencyRate->getRate();
            $destinationRate = $currentCurrencyRate->getRate();
            do {
                $loop = true;
                if ($currentCurrencyRate->getDestinationCurrency()->isEqualTo($sourceCurrency)) {
                    $loop = false;
                }

                $destinationRate = currency_exchangerate($destinationRate, $currentCurrencyRate->getRate(), 1);
                $currentCurrency = $currentCurrencyRate->getDestinationCurrency();
                $currentCurrencyRate = $this->getCurrencyRateRepository()->findRateOfCurrencyBeforeOrOnDate($currentCurrency, $date);
            } while ($loop);

            $currencyRate->setRate($destinationRate);
            $currencyRate->setDestinationRate($rate);
        } else {
            $rate = $currentCurrencyRate->getRate();
            $destinationRate = $currentCurrencyRate->getRate();
            do {
                $loop = true;
                if ($currentCurrencyRate->getDestinationCurrency()->isEqualTo($destinationCurrency)) {
                    $loop = false;
                }

                $destinationRate = currency_exchangerate($destinationRate, $currentCurrencyRate->getRate(), 1);
                $currentCurrency = $currentCurrencyRate->getDestinationCurrency();
                $currentCurrencyRate = $this->getCurrencyRateRepository()->findRateOfCurrencyBeforeOrOnDate($currentCurrency, $date);
            } while ($loop);

            $currencyRate->setRate($rate);
            $currencyRate->setDestinationRate($destinationRate);
        }
        
        return $currencyRate;
    }
    
    public function getCurrencyRateDependsOnDate(Currency $currency, DateTimeInterface $date): CurrencyRate
    {
        $currencyRate = $this->getCurrencyRateRepository()->findRateOfCurrencyBeforeOrOnDate($currency, $date);
        $user = $this->getUserRepository()->find($this->getUser()->getId());
        
        if (!($currencyRate instanceof CurrencyRate)) {
            $currencyRate = new CurrencyRate();
            $currencyRate->setSourceCurrency($currency);
            $currencyRate->setDestinationCurrency($this->getBaseCurrency());
            $currencyRate->setSourceRate($currency->getRate());
            $currencyRate->setDestinationRate(1);
            $currencyRate->preserveOriginal();
            $currencyRate->setCreator($user);
            $currencyRate->setCreatedAt($currency->getCreatedAt());
            $this->getEventDispatcher()->dispatch(VersionableListener::VERSIONABLE_SAVE, new GenericEntityEvent($currencyRate));
        }
        
        return $currencyRate;
    }
    
    private function getUnitOfWork(): UnitOfWork
    {
        return $this->getDoctrine()->getManager()->getUnitOfWork();
    }

    protected function getRepository(): CurrencyRepository
    {
        return $this->getDoctrine()->getRepository(Currency::class);
    }

    protected function getUserRepository(): UserRepository
    {
        return $this->getDoctrine()->getRepository(User::class);
    }

    private function getCurrencyRateRepository(): CurrencyRateRepository
    {
        return $this->getDoctrine()->getRepository(CurrencyRate::class);
    }
    
    private function getCurrencyRepository(): CurrencyRepository
    {
        return $this->getDoctrine()->getRepository(Currency::class);
    }

    private function getSettingManager(): SettingManager
    {
        return $this->getContainer()->get('app.setting_manager');
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->getContainer()->get('event_dispatcher');
    }
}
