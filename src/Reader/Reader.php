<?php

namespace CHMLib\Reader;

/**
 * Read data from a generic source.
 */
abstract class Reader
{
    /**
     * Set the current position.
     *
     * @param int $position
     *
     * @throws \Exception Throws an Exception in case of errors.
     */
    abstract public function setPosition($position);

    /**
     * Get the current position.
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return int
     */
    abstract public function getPosition();

    /**
     * Get the total length of the data.
     *
     * @return int
     */
    abstract public function getLength();

    /**
     * Read a fixed number of bytes as a raw string.
     *
     * @param int $length The number of bytes to read.
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return string
     */
    abstract public function readString($length);

    /**
     * Read a fixed number of bytes and return it as a byte array.
     *
     * @param int $length The number of bytes to read.
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return int[]
     */
    public function readBytes($length)
    {
        $data = $this->readString($length);

        switch ($length) {
            case 0:
                $result = array();
                break;
            case 1:
                $result = array(ord($data[0]));
                break;
            default:
                $result = unpack('C*', $data);
                break;
        }

        return $result;
    }

    /**
     * Read a byte.
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return int
     */
    public function readByte()
    {
        $bytes = $this->readBytes(1);

        return $bytes[0];
    }

    /**
     * Read an unsigned 16-bit integer (little endian).
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return int
     */
    public function readUInt16()
    {
        $chunk = unpack('v', $this->readString(2));

        return array_pop($chunk);
    }

    /**
     * Read an unsigned 32-bit integer (little endian).
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return int
     */
    public function readUInt32()
    {
        $bytes = $this->readString(4);
        $chunk = unpack('V', $bytes);
        $int = array_pop($chunk);
        if ($int < 0) {
            $bits = decbin($int);
            $int = bindec(substr($bits, 0, -1)) * 2;
            if (substr($bits, -1) === '1') {
                $int += 1;
            }
        }

        return $int;
    }

    /**
     * Read a signed 32-bit integer (little endian).
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return int
     */
    public function readInt32()
    {
        static $osIsBigEndian;
        if (!isset($osIsBigEndian)) {
            $osIsBigEndian = (pack('L', 1) === pack('N', 1)) ? true : false;
        }
        $data = $this->readString(4);
        if ($osIsBigEndian) {
            $data = strrev($data);
        }
        $chunk = unpack('l', $data);

        return array_pop($chunk);
    }

    /**
     * Read an unsigned 64-bit integer (little endian).
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return int|float
     */
    public function readUInt64()
    {
        static $nativeUnpack;

        if (!isset($nativeUnpack)) {
            $nativeUnpack = (PHP_INT_SIZE >= 8 && version_compare(PHP_VERSION, '5.6.3') >= 0) ? true : false;
        }
        if ($nativeUnpack) {
            $chunk = unpack('P', $this->readString(8));
            $result = array_pop($chunk);
            if ($result < 0) {
                $bits = decbin($result);
                $result = bindec(substr($bits, 0, -1)) * 2;
                if (substr($bits, -1) === '1') {
                    $result += 1;
                }
            }
        } else {
            $n1 = $this->readUInt32();
            $n2 = $this->readUInt32();

            $result = $n2 * 0x100000000 + $n1;
            if ($result <= PHP_INT_MAX) {
                $result = (int) $result;
            }
        }

        return $result;
    }

    /**
     * Read a GUID.
     *
     * @throws \Exception Throws an Exception in case of errors.
     *
     * @return string
     *
     * @example '{5D02926A-212E-11D0-9DF9-00A0C922E6EC}'
     */
    public function readGUID()
    {
        return sprintf(
            '{%1$08X-%2$04X-%3$04X-%4$02X%5$02X-%6$02X%7$02X-%8$02X%9$02X-%10$02X%11$02X}',
            $this->readUInt32(),
            $this->readUInt16(),
            $this->readUInt16(),
            $this->readByte(),
            $this->readByte(),
            $this->readByte(),
            $this->readByte(),
            $this->readByte(),
            $this->readByte(),
            $this->readByte(),
            $this->readByte()
        );
    }

    /**
     * Read a compressed unsigned 32-bit integer (little endian).
     *
     * @return number
     */
    public function readCompressedUInt32()
    {
        $result = 0;
        for (; ;) {
            $result <<= 7;
            $byte = $this->readByte();
            if ($byte < 0x80) {
                $result += $byte;
                break;
            }
            $result += $byte & 0x7f;
        }

        return $result;
    }
}
