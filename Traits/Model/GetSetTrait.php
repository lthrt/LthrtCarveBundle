<?php

namespace Lthrt\CarveBundle\Traits\Model;

// Copied from LthrtEntityBundle
// Removed if that Bundle is used

/**
 * GetSet Trait.
 *
 * For discussion see: http://www.epixa.com/2010/05/the-best-models-are-easy-models.html
 */
trait GetSetTrait
{
    use GetTrait;
    use SetTrait;

    /**
     * Map a call to a non-existent mutator or accessor directly to its
     * corresponding property.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     *
     * @throws \BadMethodCallException If no mutator/accessor can be found
     */
    public function __call(
        $name,
        $arguments
    ) {
        if (0 === strpos($name, 'get')) {
            $property = lcfirst(substr($name, 3));

            return $this->$property;
        }

        if (0 === strpos($name, 'set')) {
            $property = lcfirst(substr($name, 3));

            $this->$property = array_shift($arguments);

            return $this;
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new \BadMethodCallException(sprintf(
            'No method or property named `%s` exists',
            $name
        ));
    }
}
