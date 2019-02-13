<?php
namespace Page;

use Codeception\Util\Debug;

class TransactionCreatePage extends PageObject
{
    // include url of current page
    //generic template
    public static $URL = '/transaction/';

    public static $createTransactionButton = 'Transact';
    public static $memberSearchBox = '#select2-Transaction_customer-container > span';
    public static $memberSearchBoxTextField = '/html/body/span/span/span[1]/input';
    public static $memberSearchFirstResult = '#select2-Transaction_customer-results > li.select2-results__option.select2-results__option--highlighted';
    public static $paymentGatewayField = '#select2-Transaction_gateway-container > span';
    public static $paymentGatewayFirstResult = '//*[@id="select2-Transaction_gateway-results"]/li[1]';
    public static $addProductButton = '//*[@id="Transaction_subTransactions"]//button';
    public static $paymentOptionField = '#payment-option-container > div > div > button > span.filter-option.pull-left';
    public static $subtransactionAmountField = '#Transaction_subTransactions_0_amount';
    public static $customerFeeField = '#Transaction_customerFee';
    public static $companyFeeField = '#Transaction_companyFee';
    public static $saveButtonLabel = 'Save';
    public static $productSelectedMultiple = '//*[@id="Transaction_subTransactions_%d"]//ul';
    

    public static $saveButton = '//*[@id="Transaction_actions_btn_save"]';
    public static $noteField = '#Transaction_notes';

    //applicable for deposit
    public static $depositURL = '/transaction/deposit';
    public static $formTitleDeposit = 'Request Deposit Transaction';
    public static $createTransactionDepositButton = 'Deposit';
    public static $productField = '//*[@id="Transaction_subTransactions_0"]//span[contains(text(),"Nothing selected")]';
    
    //applicable for deposit with multiple products with amount
    public static $productInMultiple = '#Transaction_subTransactions_%d > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > button > span.filter-option.pull-left';
    public static $amountInMultiple  = '//*[@id="Transaction_subTransactions_%d_amount"]';
    
    //applicable for withdrawal with multiple products with amount
    public static $productInMultipleWithdrawal = '#Transaction_subTransactions_%d> div.col-md-6.col-sm-6.col-xs-12 > div > div.input-group > div > button > span.filter-option.pull-left';    
    
    //applicable for p2p with multiple products with amount
    public static $addProductButtonP2P = '//*[@id="%s-container"]/h4/button/i';
    public static $productInMultipleForP2P = '#%s > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > button > span.filter-option.pull-left';
    public static $amountInMultipleForP2P = '//*[@id="%s_amount"]';
    public static $productSelectedMultipleForP2P = '//*[@id="%s"]//ul';

    

    //applicatble for withdrawal
    public static $withDrawformTitle = 'Request Withdraw Transaction';
    public static $createTransactionWithdrawButton = 'Withdraw';
    public static $moreProductButton = '#Transaction_subTransactions > h4 > button > i' ;
    public static $hasCustomerFee = '//*[@id="Transaction_subTransactions_0_hasFee"]';

    //applicable for transfer
    public static $transferFormTitle = 'Request Transfer Transaction';
    public static $createTransactionTransferButton = 'Transfer';
    public static $productFieldTransferFrom =  '#Transaction_subTransactions_0 > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > button';
    public static $productFieldTransferTo =  '#Transaction_subTransactions_1 > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > button';
    public static $subtransactionAmountFieldFrom = '#Transaction_subTransactions_0_amount';
    public static $subtransactionAmountFieldTo = '#Transaction_subTransactions_1_amount';
    public static $productSelectedTransfer = '#Transaction_subTransactions_%d > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > div > ul > li:nth-child(1) > a > span.text';
    public static $addFromProductButton = '//*[@id="Transaction_subTransactions"]/div[1]/h4/button';
    public static $addToProductButton = '//*[@id="Transaction_subTransactions"]/div[2]/h4/button';
    public static $firstElementFromProduct = '#Transaction_subTransactions_0 > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > button > span.filter-option.pull-left';
    public static $acWalletProductField = '#Transaction_subTransactions_0 > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > div > ul > li:nth-child(1) > a > span.text';
    public static $acWalletAmountField = '//*[@id="Transaction_subTransactions_0_amount"]';
    public static $secondElementFromProduct = '#Transaction_subTransactions_2 > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > button > span.filter-option.pull-left';
    public static $asianOddsProductField = '#Transaction_subTransactions_2 > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > div > ul > li:nth-child(1) > a > span.text';
    public static $asianOddsAmointField = '//*[@id="Transaction_subTransactions_2_amount"]';
    public static $firstElementToProduct = '#Transaction_subTransactions_1 > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > button > span.filter-option.pull-left';
    public static $maxbetProductField = '#Transaction_subTransactions_1 > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > div > ul > li:nth-child(1) > a > span.text';
    public static $maxbetAmountField = '//*[@id="Transaction_subTransactions_1_amount"]';
    public static $secondElementToProduct = '#Transaction_subTransactions_3 > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > button > span.filter-option.pull-left';
    public static $matchbookProductField = '#Transaction_subTransactions_3 > div.col-md-7.col-sm-7.col-xs-12 > div > div.input-group > div > div > ul > li:nth-child(1) > a > span.text';
    public static $matchbookAmountField = '//*[@id="Transaction_subTransactions_3_amount"]';
    
    //applicable for P2P
    public static $p2pFormTitle = 'Request P2P Transfer Transaction';
    public static $createTransactionP2PButton = 'P2P Transfer';
    public static $memberSearchBoxFrom = '//*[@id="select2-Transaction_customer-container"]';
    public static $memberSearchBoxTextFieldP2PFrom = '/html/body/span/span/span[1]/input';
    public static $memberSelectFirstResultFrom = '#select2-Transaction_customer-results > li';
    public static $memberSearchBoxTo = '//*[@id="select2-toCustomer-container"]/span';
    public static $memberSearchBoxTextFieldP2PTo = '/html/body/span/span/span[1]/input';
    public static $memberSelectFirstResultTo = '#select2-toCustomer-results > li.select2-results__option.select2-results__option--highlighted';
    
    //applicable for Bonus
    public static $bonusFormTitle = 'Request Bonus Transaction';
    public static $createTransactionBonusButton = '//*[@id="wrapper"]/div[3]/div/div/div/div[1]/div/div/div/div/ul/li[5]/a';

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */

    /**
     * Basic route example for your current URL
     * You can append any additional parameter to URL
     * and use it in tests like: Page\Edit::route('/123-post');
     */
    public static function route($param)
    {
        return static::$URL.$param;
    }

    public function setMemberByFullName(string $fullName)
    {
        $this->selectMember($fullName);
    }

    public function setMemberByUsername(string $username)
    {
        $this->selectMember($username);
    }

    private function selectMember(string $queryString)
    {
        $this->tester->click(self::$memberSearchBox);
        $this->tester->fillField(self::$memberSearchBoxTextField, $queryString);
        $this->tester->wait(3);
        $this->tester->click(self::$memberSearchFirstResult);
        $this->tester->wait(1);
    }

    public function selectPaymentOption($paymentOptionLabel = '')
    {
        $this->tester->click(self::$paymentOptionField);
        $this->tester->wait(1);
        $this->tester->click($paymentOptionLabel);
    }

    public function selectFirstPaymentGateway()
    {
        $this->tester->click(self::$paymentGatewayField);
        $this->tester->wait(3);
        $this->tester->click(self::$paymentGatewayFirstResult);
    }

    public function setProductAndAmountOnWithdrawal($memberProductLabel, $transactionAmount)
    {
        $this->setProductAndAmount($memberProductLabel, $transactionAmount);
        $this->tester->click(self::$hasCustomerFee);
    }

    public function setProductAndAmount($memberProductLabel, $transactionAmount)
    {
        $this->tester->wait(1);
        $this->tester->executeJs("document.evaluate('". self::$productField ."', document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();");
        $this->tester->wait(1);
        $this->tester->click($memberProductLabel);
        $this->tester->fillField(self::$subtransactionAmountField, $transactionAmount);
    }

    public function setFeesAndNote($customerFee = 0, $companyFee = 0, $note = '')
    {
        $this->tester->fillField(self::$customerFeeField, $customerFee);
        $this->tester->fillField(self::$companyFeeField, $companyFee);
        $this->setNote($note);
    }

    public function setNote(string $note = '')
    {
        $this->tester->fillField(self::$noteField, $note);
    }

    public function submitTransaction()
    {
        $this->tester->scrollTo(self::$saveButton);
        $this->tester->see(self::$saveButtonLabel);
        $this->tester->click(self::$saveButtonLabel);
    }
    
    public function setTransferProductAndAmount(string $productFrom = '', string $productTo = '', int $transactionAmountFrom, int $transactionAmountTo, string $note = '')
    {
        $i = 0;
        $this->tester->wait(3);
        $this->tester->executeJs('$("'. self::$productFieldTransferFrom .'").click();');
        $this->tester->executeJs('$("'. sprintf(self::$productSelectedTransfer, $i) .'").click();');
        $this->tester->fillField(self::$subtransactionAmountFieldFrom, $transactionAmountFrom);
        $this->tester->wait(1);
        $this->tester->executeJs('$("'. self::$productFieldTransferTo .'").click();');
        $this->tester->executeJs('$("'. sprintf(self::$productSelectedTransfer, ++$i) .'").click();');
        $this->tester->fillField(self::$subtransactionAmountFieldTo, $transactionAmountTo);
        $this->tester->fillField(self::$noteField, $note);
    }
    
    public function setMemberFromP2P(string $fullNameFrom = '')
    {
        $this->tester->click(self::$memberSearchBoxFrom);
        $this->tester->fillField(self::$memberSearchBoxTextFieldP2PFrom, $fullNameFrom);
        $this->tester->wait(3);
        $this->tester->click(self::$memberSelectFirstResultFrom);
    }

    public function setMemberToP2P(string $fullNameTo = '')
    {
        $this->tester->click(self::$memberSearchBoxTo);
        $this->tester->fillField(self::$memberSearchBoxTextFieldP2PTo, $fullNameTo);
        $this->tester->wait(3);
        $this->tester->click(self::$memberSelectFirstResultTo);
    }
    
    public function setProductAndAmountOnP2p(string $productFrom = '', string $productTo = '', int $transactionAmountFrom, int $transactionAmountTo, string $note = '')
    {
        $i = 0;
        $this->tester->wait(3);
        $this->tester->executeJs('$("'. self::$productFieldTransferFrom .'").click();');
        $this->tester->executeJs('$("'. sprintf(self::$productSelectedTransfer, $i) .'").click();');
        $this->tester->fillField(self::$subtransactionAmountFieldFrom, $transactionAmountFrom);
        $this->tester->wait(1);
        $this->tester->executeJs('$("'. self::$productFieldTransferTo .'").click();');
        
        $this->tester->click(sprintf(self::$productSelectedMultiple, ++$i));
        $this->tester->fillField(self::$subtransactionAmountFieldTo, $transactionAmountTo);
        $this->tester->fillField(self::$transferNoteField, $note);
    }

    public function addRandomProductsOnDeposit(array $multipleAmount = []): void
    {
        $numberOfAmountToBeSetForEachProduct = count($multipleAmount);
        $defaultNumberOfProductDisplayedToFill = 1;
        foreach ($multipleAmount as $i => $amount) {
            $this->tester->wait(1);
            if ($defaultNumberOfProductDisplayedToFill >= $numberOfAmountToBeSetForEachProduct) {
                $this->tester->click(self::$addProductButton);
            }
            
            $this->tester->executeJs('$("'. sprintf(self::$productInMultiple, $i) .'").click();');
            $this->tester->wait(1);
            $this->tester->click(sprintf(self::$productSelectedMultiple, $i));
            $this->tester->fillField(sprintf(self::$amountInMultiple, $i), $amount);
            
            $defaultNumberOfProductDisplayedToFill++;
        }
    }

    public function addRandomProductsOnWithdrawal(array $multipleAmounts = []): void
    {
        $numberOfAmountToBeSetForEachProduct = count($multipleAmounts);
        $defaultNumberOfProductDisplayedToFill = 1;
        foreach ($multipleAmounts as $i => $amount) {
            $this->tester->wait(1);
            if ($defaultNumberOfProductDisplayedToFill >= $numberOfAmountToBeSetForEachProduct) {
                $this->tester->click(self::$addProductButton);
            }
            
            $this->tester->executeJs('$("'. sprintf(self::$productInMultipleWithdrawal, $i) .'").click();');
            $this->tester->wait(1);
            $this->tester->click(sprintf(self::$productSelectedMultiple, $i));
            $this->tester->fillField(sprintf(self::$amountInMultiple, $i), $amount);

            $defaultNumberOfProductDisplayedToFill++;
        }
    }

    public function setMultipleProductWithAmountsForSender(array $amounts = []): void
    {
        $this->selectMultipleProductsWithAmounts($amounts , 'from');
    }
    
    public function setMultipleProductWithAmountsForReceiver(array $amounts = []): void
    {
        $this->selectMultipleProductsWithAmounts($amounts , 'to');
    }

    public function setFirstProductAndAmountToBeDeducted(int $amount): void
    {
        $this->tester->executeJs('$("'. self::$firstElementFromProduct .'").click();');
        $this->tester->wait(1);
        $this->tester->executeJs('$("'. self::$acWalletProductField .'").click();');
        $this->tester->fillField(self::$acWalletAmountField, $amount);
    }

    public function setSecondProductAndAmountToBeDeducted(int $amount): void
    {
        $this->tester->executeJs('$("'. self::$secondElementFromProduct .'").click();');
        $this->tester->wait(1);
        $this->tester->executeJs('$("'. self::$asianOddsProductField .'").click();');
        $this->tester->fillField(self::$asianOddsAmointField, $amount);
    }
    
    public function setFirstProductAndAmountToBeTransferred(int $amount): void
    {
        $this->tester->executeJs('$("'. self::$firstElementToProduct .'").click();');
        $this->tester->wait(1);
        $this->tester->executeJs('$("'. self::$maxbetProductField .'").click();');
        $this->tester->fillField(self::$maxbetAmountField, $amount);
    }

    public function setSecondProductAndAmountToBeTransferred(int $amount): void
    {
        $this->tester->executeJs('$("'. self::$secondElementToProduct .'").click();');
        $this->tester->wait(1);
        $this->tester->executeJs('$("'. self::$matchbookProductField .'").click();');
        $this->tester->fillField(self::$matchbookAmountField, $amount);
     
        $this->tester->makeScreenShot();
    }
    
    public function addFromProductButton(): void
    {
        //$this->tester->executeJs('$("'. self::$addFromProductButton .'").click();');
        $this->tester->click(self::$addFromProductButton);
        $this->tester->wait(1);
    }
    
    public function addToProductButton(): void
    {
        //$this->tester->executeJs('$("'. self::$addToProductButton .'").click();');
        $this->tester->click(self::$addToProductButton);
        $this->tester->wait(1);
    }
    
    private function selectMultipleProductsWithAmounts(array $multipleAmounts = [], string $origin): void
    {
        $numberOfAmountToBeSetForEachProduct = count($multipleAmounts);
        $defaultNumberOfProductDisplayedToFill = 1;
        foreach ($multipleAmounts as $amount) {
            if ($defaultNumberOfProductDisplayedToFill >= $numberOfAmountToBeSetForEachProduct) {
                $this->tester->wait(1);
                $this->tester->click(sprintf(self::$addProductButtonP2P, $origin));
            }
            
            $defaultNumberOfProductDisplayedToFill++;
        }
        
        $productElements = $this->getElementContainer("div", "class", $origin . "-product-list", "/div", "id");
        $i = 0;
        foreach ($productElements as $element) {
            if ($i >= $numberOfAmountToBeSetForEachProduct) {
                continue;
            }
            $this->tester->executeJs('$("'. sprintf(self::$productInMultipleForP2P, $element) .'").click();');
            $this->tester->wait(1);
            $this->tester->click(sprintf(self::$productSelectedMultipleForP2P, $element));
            $this->tester->fillField(sprintf(self::$amountInMultipleForP2P, $element), $multipleAmounts[$i]);
            
            $i++;
        } 
    }

    private function getElementContainer(
        string $element,
        string $type,
        string $query,
        string $lookUp,
        string $returnIndex): 
        array {
        
        return $this->tester->grabMultiple("//" . $element . "[@" . $type . "='". $query ."']" . $lookUp, $returnIndex);
    }
}
