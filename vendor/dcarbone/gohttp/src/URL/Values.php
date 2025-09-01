<?php declare(strict_types=1);

namespace DCarbone\Go\HTTP\URL;

/**
 * Class Values
 * @package DCarbone\Go\HTTP\URL
 */
class Values implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
    /** @var array */
    private $values = [];

    /**
     * Values constructor.
     * @param array $seed
     */
    public function __construct(array $seed = [])
    {
        foreach ($seed as $k => $v) {
            $this->add($k, $v);
        }
    }

    /**
     * @param string $key
     * @return string
     */
    public function get(string $key): string
    {
        if (isset($this->values[$key])) {
            return $this->values[$key][0];
        }
        return '';
    }

    /**
     * @param string $key
     * @return string[]
     */
    public function getAll(string $key): array
    {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        }
        return [];
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function set(string $key, string $value)
    {
        $this->values[$key] = [$value];
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function add(string $key, string $value)
    {
        if (isset($this->values[$key])) {
            $this->values[$key][] = $value;
        } else {
            $this->values[$key] = [$value];
        }
    }

    /**
     * @param string $key
     */
    public function delete(string $key)
    {
        unset($this->values[$key]);
    }

    /**
     * @return array
     */
    public function toPsr7Array(): array
    {
        return $this->values;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * @return string|array
     */
    public function current()
    {
        return current($this->values);
    }

    public function next()
    {
        next($this->values);
    }

    /**
     * @return string
     */
    public function key()
    {
        return key($this->values);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return null !== key($this->values);
    }

    public function rewind()
    {
        reset($this->values);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    /**
     * @param string $offset
     * @return string
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     * @param string $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->values;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $str = '';
        foreach ($this->values as $k => $vs) {
            foreach ($vs as $v) {
                if ('' !== $str) {
                    $str .= '&';
                }
                if ('' === $v) {
                    $str .= $k;
                } else {
                    $str .= sprintf('%s=%s', $k, $this->encode($v));
                }
            }
        }
        return $str;
    }

    /**
     * @param string $v
     * @return string
     */
    protected function encode(string $v): string
    {
        return $v;
    }
}