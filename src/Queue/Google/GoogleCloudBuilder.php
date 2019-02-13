<?php declare(strict_types=1);

namespace IPresence\DomainEvents\Queue\Google;

use Google\Cloud\PubSub\PubSubClient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class GoogleCloudBuilder
{
    /**
     * @var PubSubClient
     */
    private $client;

    /**
     * @var string
     */
    private $topic;

    /**
     * @var string
     */
    private $subscription;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @return GoogleCloudBuilder
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function withConfig(array $config)
    {
        $this->topic = $this->getParamOrFail($config, 'topic');
        $this->subscription = $this->getParamOrFail($config, 'subscription');

        $params = [];
        if (isset($config['project_id'])) {
            $params['projectId'] = $config['project_id'];
        }
        $this->client = new PubSubClient($params);

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function withLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return GoogleCloudQueue
     */
    public function build(): GoogleCloudQueue
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return new GoogleCloudQueue($this->client, $this->topic, $this->subscription, $this->logger);
    }

    /**
     * @param array  $config
     * @param string $param
     *
     * @return mixed
     */
    private function getParamOrFail(array $config, string $param)
    {
        if (!isset($config[$param])) {
            throw new RuntimeException("The configuration is missing the required parameter: $param");
        }

        return $config[$param];
    }
}
