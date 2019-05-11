
Ethereum Smart Contract
===

Ethereum Smart Contract Wrapper

Install
------------
```
composer require gri3li/ethereum-smart-contract
```


Usage
-----

Create contract instance:

```php
use gri3li\EthereumSmartContract;

$instance = EthereumSmartContract::createByHost(
    'http://localhost:8545',
    '1', // mainnet
    '0xB8c77482e45F1F44dE1745F52C74426C631bDD52', // contract address
    file_get_contents('path_to_contract_abi_file.json') // abi string
);
```

Reading, for example, erc20 token get balance:

```php
$response = $instance->read('balanceOf', ['0x227390eeba512120c16C239B6556C0992022E961']);
var_dump($response);

/* array(1) {
  'balance' =>
  class phpseclib\Math\BigInteger#47 (2) {
    public $value =>
    string(24) "0x01c3ca8bcdc38115a80020"
    public $engine =>
    string(3) "gmp"
  }
} */

/** @var \BI\BigInteger $balance */
$balance = $response['balance'];
var_dump($balance->toString());

```

Writing, for example, erc20 token send transfer:

```php        
$fromPrivateKey = '4ffe6b52e5f649794dd4f75ed91276ad0dd417ec24cd24ba22802ea50e9d34fd';
$toAddress = '0x227390eeba512120c16C239B6556C0992022E962';
$amount = '1000000000000000000';
$gasLimit = '800000';
$gasPrice = '12000000000';
$txHash = $instance->write('transfer', [$toAddress, $amount], $fromPrivateKey, $gasPrice, $gasLimit);
```
