<?php
namespace NetricTest\Crypt;

use PHPUnit_Framework_TestCase;
use Netric\Crypt\BlockCipher;

class BlockCipherTest extends PHPUnit_Framework_TestCase
{
    public function testEncrypt()
    {
        $toEncrypt = "My Text";
        $blockCyper = new BlockCipher("1234567891234567");
        $encrypted = $blockCyper->encrypt($toEncrypt);
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($toEncrypt, $encrypted);

        // Different key
        $blockCyper2 = new BlockCipher("1234567891234568");
        $encrypted2 = $blockCyper2->encrypt($toEncrypt);
        $this->assertNotEquals($toEncrypt, $encrypted2);
        $this->assertNotEquals($encrypted, $encrypted2);
    }

    public function testDecrypt()
    {
        $toEncrypt = "password";

        $blockCyper = new BlockCipher("1234567891234567");
        $encrypted = $blockCyper->encrypt($toEncrypt);
        $this->assertNotEquals($toEncrypt, $encrypted);

        $decrypted = $blockCyper->decrypt($encrypted);
        $this->assertEquals($toEncrypt, $decrypted);
    }

    public function testDetermineAlgorithm()
    {
        $algo = BlockCipher::determineAlgorithm("NOALGO");
        $this->assertEquals($algo, BlockCipher::ALGO_LEGACY);

        $algo = BlockCipher::determineAlgorithm("2-NOALGO");
        $this->assertEquals($algo, BlockCipher::ALGO_V2);
    }
}
