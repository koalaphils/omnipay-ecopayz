<?php

namespace ZendeskBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\ArrayInput;
use GuzzleHttp\Exception\RequestException;
use Zendesk\API\Exceptions\ApiResponseException;

class CreateZendeskTokenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('zendesk:oauth:token:generate')
            ->setDescription('Create zendesk oauth token')
            ->addArgument('client_id', InputArgument::OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            '============================================',
            'Zendesk OAuth Token Generator ::: ENV => '
            . strtoupper($this->getContainer()->get('kernel')->getEnvironment()),
            '============================================',
        ]);
        $authenticated = false;
        $token = array_get($this->getContainer()->getParameter('zendesk_security_options'), 'token', '');
        if ($token !== '') {
            $output->writeln('Current Token: ' . $token);
            $authenticated = $this->testToken($token);
            if (!$authenticated) {
                $output->writeln([
                    'Current token is not valid',
                    '============================================',
                ]);
            }
        }

        if (!$authenticated) {
            $token = $this->generateToken($input, $output);
            if ($token['status'] >= 200 && $token['status'] < 300) {
                $output->writeln([
                    'OAuth Token was successfully generated!!!',
                    '============================================',
                    'Token: ' . $token['body']['token']['full_token'],
                    '--------------------------------------------',
                    'Body:',
                    \GuzzleHttp\json_encode($token['body']),
                ]);
                $this->save($token['body']['token']['full_token']);
                $output->writeln([
                    '============================================',
                    'Token saved!!!',
                ]);
                // clear cache
                $cacheClearCommand = $this->getApplication()->find('cache:clear');
                $arguments = [
                    'command' => 'cache:clear',
                    '--env' => $this->getContainer()->get('kernel')->getEnvironment(),
                ];

                $input = new ArrayInput($arguments);
                $cacheClearCommand->run($input, $output);
            } else {
                $output->writeln([
                    'Something went wrong!!!',
                    '============================================',
                    "Status: $token[status]",
                    '--------------------------------------------',
                    'Body:',
                    \GuzzleHttp\json_encode($token['body']),
                ]);
            }
        }
    }

    protected function getUrl()
    {
        if ($this->getContainer()->hasParameter('zendesk_domain')) {
            $domain = $this->getContainer()->getParameter('zendesk_domain');
        } else {
            $domain = 'zendesk.com';
        }

        if ($this->getContainer()->hasParameter('zendesk_api_base')) {
            $version = $this->getContainer()->getParameter('zendesk_api_base');
        } else {
            $version = 'v2';
        }

        $subdomain = $this->getContainer()->getParameter('zendesk_subdomain');

        return "https://$subdomain.$domain/api/$version";
    }

    protected function testToken($token)
    {
        try {
            $response = $this->send('GET', '/users.json', [], [
                'Authorization' => "Bearer $token",
            ]);

            return $response->getStatusCode() == 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function generateToken(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'We need to know your zendesk information to create a token',
        ]);
        $clientId = $this->getQuestionHelper()->ask($input, $output, new Question('Client Identifier: '));
        $tokenVerify = $this->getQuestionHelper()->ask($input, $output, new \Symfony\Component\Console\Question\ConfirmationQuestion('Token Verification?', false));
        $email = $this->getQuestionHelper()->ask($input, $output, new Question('Email Address: '));
        if ($tokenVerify) {
            $email .= '/token';
        }
        $password = $this->getQuestionHelper()->ask($input, $output, new Question($tokenVerify ? 'Token: ' : 'Password: '));

        $response = $this->send(
            'POST',
            '/oauth/tokens.json',
            [
                'token' => [
                    'client_id' => $this->getClientId($clientId, $email, $password),
                    'scopes' => ['read', 'write'],
                ],
            ],
            [
                'Content-Type' => 'application/json',
            ],
            [
                'auth' => [
                    $email, $password,
                ],
            ]
        );

        return ['status' => $response->getStatusCode(), 'body' => \GuzzleHttp\json_decode($response->getBody()->getContents(), true)];
    }

    protected function getClientId($client, $email, $password)
    {
        $response = $this->send('GET', '/oauth/clients.json', [], [], [
            'auth' => [
                $email, $password,
            ],
        ]);
        $clientId = null;
        $responseContent = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        foreach ($responseContent['clients'] as $clientInfo) {
            if ($client == $clientInfo['identifier']) {
                $clientId = $clientInfo['id'];

                break;
            }
        }
        if (is_null($clientId)) {
            throw new Exception('Client Identifier does not exist', 404);
        }

        return $clientId;
    }

    /**
     * @param type  $method
     * @param type  $uri
     * @param type  $params
     * @param type  $headers
     * @param array $options
     *
     * @return \Psr\Http\Message\MessageInterface
     *
     * @throws ApiResponseException
     */
    protected function send($method, $uri, $params = [], $headers = [], $options = [])
    {
        $url = $this->getUrl() . $uri;
        $client = new Client();
        try {
            $request = new Request($method, $url, $headers, \GuzzleHttp\Psr7\stream_for(json_encode($params)));
            $response = $client->send($request, $options);
            $response->getBody();

            return $response;
        } catch (RequestException $e) {
            throw new ApiResponseException($e);
        }
    }

    protected function save($token)
    {
        $paramsFile = $this->getContainer()->getParameter('kernel.root_dir') . '/config/parameters.yml';
        $params = Yaml::parse(file_get_contents($paramsFile));
        array_set($params, 'parameters.zendesk_security_options.token', $token);
        array_set($params, 'parameters.zendesk_security_type', 'oauth');

        file_put_contents($paramsFile, Yaml::dump($params));
    }

    /**
     * @return QuestionHelper
     */
    protected function getQuestionHelper()
    {
        return $this->getHelper('question');
    }
}
