<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Crypt;
use Netric\Application\Exception\AccountAlreadyExistsException;

/**
 * Symmetric cipher for encryption given a supplied key
 */
class BlockCipher
{
    /**
     * Default key constant
     *
     * NOTE: This should be replaces since it is not secure to store the key
     * in source code.
     *
     * @var string
     */
    private $key = "";

    /**
     * Which MCRYPT cipher to use
     *
     * @const
     * @var string
     */
    private $cipher = MCRYPT_RIJNDAEL_256;

    /**
     * Which mcrypt mode to use
     *
     * @var string
     */
    private $mode = MCRYPT_MODE_ECB;

    /**
     * Determine which algorithm to use for cipher
     *
     * @var
     */
    private $algorithm = "";
    const ALGO_LEGACY = "";
    const ALGO_V2 = "2";

    /**
     * BlockCipher constructor.
     *
     * @param string $key The key to use the symmetric chipher
     * @param string $algorithm The char of the algorhithm to use
     */
    public function __construct($key, $algorithm=self::ALGO_V2)
    {
        $this->algorithm = $algorithm;
        $this->key = $key;
    }

    /**
     * Encrypt a block of text
     *
     * @param $text
     * @return string encrypted text
     */
    public function encrypt($text)
    {
        switch ($this->algorithm) {
            case self::ALGO_LEGACY:
            default:
                return $this->encryptLegacy($text);
        }
    }

    /**
     * Decrypt a block of text
     *
     * @param string $text
     * @return string decrypted text
     */
    public function decrypt($text)
    {
        switch ($this->algorithm) {
            case self::ALGO_LEGACY:
            default:
                return $this->decryptLegacy($text);
        }
    }

    /**
     * Determine which algorithm to use based on the prefix of the text
     *
     * We store the algorithm in the first char followed by a -. Version 2 of our
     * algorhithm would look like 2-<encrypedtext>. Legacy encryption will not have
     * a prefix.
     *
     * @param string $text
     * @return string one of the supported self::ALGO_*
     */
    public static function determineAlgorithm($text)
    {
        if (strlen($text) < 2) {
            return self::ALGO_LEGACY;
        }

        /*
         * We check each of the supported algorithms here to make sure
         * an unsupported algorithm is not returned.
         */
        if ('-' == $text[1]) {
            switch ($text[0]) {
                case self::ALGO_V2:
                    return self::ALGO_V2;
            }
        }

        // Assume legacy or broken text
        return self::ALGO_LEGACY;
    }

    /**
     * Encrypt using legacy algorithm from netric v1
     *
     * @param string $text
     * @return string
     */
    private function encryptLegacy($text)
    {
        return trim(
            base64_encode(
                mcrypt_encrypt(
                    MCRYPT_RIJNDAEL_256,
                    $this->key,
                    $text,
                    MCRYPT_MODE_ECB,
                    mcrypt_create_iv(
                        mcrypt_get_iv_size(
                            MCRYPT_RIJNDAEL_256,
                            MCRYPT_MODE_ECB
                        ),
                        MCRYPT_RAND
                    )
                )
            )
        );
    }

    /**
     * Decrypt using legacy algorithm from netric v1
     *
     * @param string $text
     * @return string
     */
    private function decryptLegacy($text)
    {
        return trim(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_256,
                $this->key,
                base64_decode($text),
                MCRYPT_MODE_ECB,
                mcrypt_create_iv(
                    mcrypt_get_iv_size(
                        MCRYPT_RIJNDAEL_256,
                        MCRYPT_MODE_ECB
                    ),
                    MCRYPT_RAND
                )
            )
        );
    }
}