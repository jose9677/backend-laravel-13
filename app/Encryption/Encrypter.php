<?php

namespace App\Encryption;

use Exception;
use InvalidArgumentException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Support\Str;

class Encrypter implements EncrypterContract
{
    protected string $key;
    
    public function __construct(string $key)
    {
        if (Str::startsWith($key, 'base64:')) {
            $key = substr($key, 7);
        }

        $decodeKey = base64_decode($key, true);

        if ($decodeKey === false) {
            throw new InvalidArgumentException('La clave no es una cadena base64 válida.');
        }

        if (strlen($decodeKey) !== 32) {
            throw new InvalidArgumentException('La clave decodificada debe tener exactamente 32 bytes para AES-256.');
        }

        $this->key = $decodeKey;
    }

    /**
     * Encriptación limpia (Reemplazo del nativo de Laravel)
     */
    public function encrypt($value, $serialize = true): string
    {
        $payload = $serialize ? json_encode($value, JSON_THROW_ON_ERROR) : $value;

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($payload, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new Exception('Error al encriptar los datos.');
        }

        // Generar MAC (HMAC-SHA256) para asegurar integridad
        $mac = hash_hmac('sha256', $iv . $encrypted, $this->key, true);

        // Formato: MAC + IV + CIPHERTEXT
        $payloadRaw = $mac . $iv . $encrypted;

        return base64_encode($payloadRaw);
    }    

    /**
     * Puente de Encriptación específico para CryptoJS (Angular)
     */
    public function cryptoAesEncrypt(string $passphrase, mixed $value): string
    {
        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';
        
        while (strlen($salted) < 48) {
            $dx = md5($dx . $passphrase . $salt, true);
            $salted .= $dx;
        }
        
        $key = substr($salted, 0, 32);
        $iv  = substr($salted, 32, 16);
        
        $encrypted_data = openssl_encrypt(json_encode($value, JSON_THROW_ON_ERROR), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        $data = [
            "ct" => base64_encode($encrypted_data), 
            "iv" => bin2hex($iv), 
            "s"  => bin2hex($salt)
        ];
        
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * Desencriptación de datos provenientes de CryptoJS (Angular)
     */
    public function cryptoJsAesDecrypt(string $passphrase, string $jsonString): mixed
    {
        $jsondata = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        
        try {
            $salt = hex2bin($jsondata["s"]);
            $iv  = hex2bin($jsondata["iv"]);
        } catch(Exception $e) { 
            return $e->getMessage(); 
        }
        
        $ct = base64_decode($jsondata["ct"]);
        $concatedPassphrase = $passphrase . $salt;
        
        $md5 = [];
        $md5[0] = md5($concatedPassphrase, true);
        $result = $md5[0];
        
        for ($i = 1; $i < 3; $i++) {
            $md5[$i] = md5($md5[$i - 1] . $concatedPassphrase, true);
            $result .= $md5[$i];
        }
        
        $key = substr($result, 0, 32);
        $data = openssl_decrypt($ct, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Desencriptación del método nativo
     */
    public function decrypt($payload, $unserialize = true): mixed
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new Exception('Payload inválido.');
        }

        $macSize = 32; // HMAC-SHA256
        $ivSize = openssl_cipher_iv_length('aes-256-cbc');

        if (strlen($decoded) < $macSize + $ivSize) {
            throw new Exception('Payload demasiado corto.');
        }

        $mac = substr($decoded, 0, $macSize);
        $iv = substr($decoded, $macSize, $ivSize);
        $encrypted = substr($decoded, $macSize + $ivSize);

        $calculateMac = hash_hmac('sha256', $iv . $encrypted, $this->key, true);
        if (!hash_equals($mac, $calculateMac)) {
            throw new Exception('El MAC no coincide. Datos manipulados.');
        }

        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $this->key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new Exception('Error al descifrar los datos.');   
        }

        return $unserialize ? json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR) : $decrypted;
    }

    public function getKey(): string
    {
        return base64_encode($this->key);
    }

    // Requeridos por el contrato de Laravel 13 para evitar errores de abstracción
    public function getAllKeys(): array
    {
        return [$this->getKey()];
    }

    public function getPreviousKeys(): array
    {
        return [];
    }
}