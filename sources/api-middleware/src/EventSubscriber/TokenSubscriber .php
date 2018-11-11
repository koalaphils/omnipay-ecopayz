<?php
	// src/EventSubscriber/TokenSubscriber.php
	namespace App\EventSubscriber;

	use App\Controller\TokenAuthenticatedController;
	use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
	use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
	use Symfony\Component\EventDispatcher\EventSubscriberInterface;
	use Symfony\Component\HttpKernel\KernelEvents;

	// add the new use statement at the top of your file
	use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

	# zimi using logger
	use Psr\Log\LoggerInterface;

	class TokenSubscriber implements EventSubscriberInterface
	{
	    private $tokens;
	    private $logger;

	    public function __construct($tokens, LoggerInterface $logger)
	    {
	        $this->tokens = $tokens;
	        $this->logger = $logger;
	    }

	    public function onKernelController(FilterControllerEvent $event)
	    {
	        $this->logger->debug('[zimi][info] onKernelController()');	   
	        $controller = $event->getController();

	        /*
	         * $controller passed can be either a class or a Closure.
	         * This is not usual in Symfony but it may happen.
	         * If it is a class, it comes in array format
	         */
	        if (!is_array($controller)) {
	            return;
	        }

	        if ($controller[0] instanceof TokenAuthenticatedController) {
	            $token = $event->getRequest()->query->get('token');
	            if (!in_array($token, $this->tokens)) {
	                throw new AccessDeniedHttpException('This action needs a valid token!');
	            }

	            // mark the request as having passed token authentication
        		$event->getRequest()->attributes->set('auth_token', $token);

	        }
	    }

	    public function onKernelResponse(FilterResponseEvent $event)
		{
		    $this->logger->debug('[zimi][info] onKernelResponse()');	   
		    // check to see if onKernelController marked this as a token "auth'ed" request
		    if (!$token = $event->getRequest()->attributes->get('auth_token')) {
		        return;
		    }

		    $response = $event->getResponse();

		    // create a hash and set it as a response header
		    $hash = sha1($response->getContent().$token);
		    $response->headers->set('X-CONTENT-HASH', $hash);
		}

	    public static function getSubscribedEvents()
	    {
	        
	        return array(
	            KernelEvents::CONTROLLER => 'onKernelController',
	            KernelEvents::RESPONSE => 'onKernelResponse',
	        );
	    }
	}