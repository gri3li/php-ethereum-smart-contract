<?php

namespace gri3li\EthereumSmartContract;

use phpseclib\Math\BigInteger;
use Web3\Contract;
use Web3\Providers\IProvider;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Utils;
use Web3\Web3;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumUtil\Util;

/**
 * Ethereum Smart Contract Wrapper Class
 * @author Mikhail Gerasimov <migerasimoff@gmail.com>
 */
class EthereumSmartContract
{
    /**
     * @var string
     */
    private $chainId;

    /**
     * @var string
     */
    private $address;

    /**
     * @var Contract
     */
    private $contract;

    /**
     * @var Web3
     */
    private $web3;

    /**
     * EthereumSmartContract constructor.
     * @param Web3 $web3
     * @param string $chainId
     * @param string $address
     * @param string $abi
     */
    public function __construct(Web3 $web3, string $chainId, string $address, string $abi)
    {
        $this->web3 = $web3;
        $this->chainId = $chainId;
        $this->address = $address;
        $this->contract = new Contract($web3->getProvider(), $abi);
    }

    /**
     * Create EthereumSmartContract instance by Ethereum node provider
     * @param IProvider $provider
     * @param string $chainId
     * @param string $address
     * @param string $abi
     * @return self
     */
    public static function createByProvider(IProvider $provider, string $chainId, string $address, string $abi): self
    {
        $web3 = new Web3($provider);

        return new self($web3, $chainId, $address, $abi);
    }

    /**
     * Create EthereumSmartContract instance by Ethereum node address
     * @param string $host
     * @param string $chainId
     * @param string $address
     * @param string $abi
     * @param float $timeout
     * @return self
     */
    public static function createByHost(string $host, string $chainId, string $address, string $abi, float $timeout = 1): self
    {
        $provider = new HttpProvider(new HttpRequestManager($host, $timeout));
        $web3 = new Web3($provider);

        return new self($web3, $chainId, $address, $abi);
    }

    /**
     * Query/Read Contract
     * @param string $method
     * @param array $arguments
     * @return array
     * @throws \Exception
     */
    public function read(string $method, array $arguments = []): array
    {
        $params = [$method];
        foreach ($arguments as $argument) {
            $params[] = $argument;
        }
        $cbResult = null;
        $params[] = function ($err, $response) use(&$cbResult) {
            if ($err) {
                throw $err;
            }
            $cbResult = $response;
        };
        call_user_func_array([$this->contract->at($this->address), 'call'], $params);

        return $cbResult;
    }

    /**
     * Write Smart Contract
     * @param string $method
     * @param array $arguments
     * @param string $privateKey
     * @param string $gasPrice
     * @param string $gasLimit
     * @return string
     * @throws \Exception
     */
    public function write(string $method, array $arguments, string $privateKey, string $gasPrice, string $gasLimit = '800000'): string
    {
        $params = [$method];
        foreach ($arguments as $argument) {
            $params[] = $argument;
        }

        $functionData = '0x' . call_user_func_array([$this->contract->at($this->address), 'getData'], $params);
        $address = $this->privateKeyToAddress($privateKey);
        $nonce = $this->getNonce($address);

        $transaction = new Transaction([
            'from' => $address,
            'to' => $this->address,
            'chainId' => $this->chainId,
            'nonce' => '0x' . $nonce->toHex(),
            'gas' => Utils::toHex($gasLimit, true),
            'gasPrice' => Utils::toHex($gasPrice, true),
            'value' => Utils::toHex('0'),
            'data' => $functionData,
        ]);

        $signedTransaction = '0x' . $transaction->sign($privateKey);

        $txHash = '';
        $this->web3->eth->sendRawTransaction($signedTransaction, function ($err, $response) use (&$txHash) {
            if ($err) {
                throw $err;
            }
            $txHash = $response;
        });

        return $txHash;
    }

    /**
     * @param $from
     * @return BigInteger
     * @throws \Exception
     */
    protected function getNonce(string $from): BigInteger
    {
        $nonce = null;
        $this->web3->eth->getTransactionCount($from, 'pending', function ($err, $response) use (&$nonce) {
            if ($err) {
                throw $err;
            }
            /** @var BigInteger $nonce */
            $nonce = $response;
        });

        return $nonce;
    }

    /**
     * @param string $privateKey
     * @return string
     */
    protected function privateKeyToAddress(string $privateKey): string
    {
        $util = new Util();
        $publicKey = $util->privateKeyToPublicKey($privateKey);
        $address = $util->publicKeyToAddress($publicKey);

        return $address;
    }
}
