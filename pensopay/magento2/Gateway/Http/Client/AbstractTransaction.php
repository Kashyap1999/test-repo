<?php
namespace PensoPay\Payment\Gateway\Http\Client;

use PensoPay\Payment\Model\Adapter\PensoPayAdapter;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractTransaction
 */
abstract class AbstractTransaction implements ClientInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var LoggerInterface
     */
    protected $monolog;

    /**
     * @var PensoPayAdapter
     */
    protected $adapter;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param PensoPayAdapter $transaction
     */
    public function __construct(Logger $logger, \Psr\Log\LoggerInterface $monolog, PensoPayAdapter $adapter)
    {
        $this->logger = $logger;
        $this->monolog = $monolog;
        $this->adapter = $adapter;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $data = $transferObject->getBody();
        $log = [
            'request' => $data,
            'client' => static::class
        ];
        $response['object'] = [];

        try {
            $response['object'] = $this->process($data);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            throw new ClientException($e->getMessage());
        } finally {
            $log['response'] = (array) $response['object'];
            $this->logger->debug($log);
        }

        return $response;
    }

    /**
     * Process http request
     * @param array $data
     * @return array
     */
    abstract protected function process(array $data);
}
