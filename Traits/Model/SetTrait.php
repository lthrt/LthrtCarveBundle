<?php

namespace Lthrt\CarveBundle\Traits\Model;

// Copied from LthrtEntityBundle
// Removed if that Bundle is used

/**
 * Get Trait.
 *
 * For discussion see: http://www.epixa.com/2010/05/the-best-models-are-easy-models.html
 */
trait SetTrait
{
    /**
     * This is not meant to be used alone.  Use GetSetTrait.
     *
     * Map a call to set a property to its corresponding mutator if it exists.
     * Otherwise, set the property directly.
     *
     * Ignore any properties that begin with an underscore so not all of our
     * protected properties are exposed.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @throws \LogicException If no mutator/property exists by that name
     */
    public function __set(
        $name,
        $value
    ) {
        if ('_' != $name[0]) {
            $mutator = 'set' . ucfirst($name);
            if (method_exists($this, $mutator)) {
                $this->$mutator($value);

                return;
            }

            if (property_exists($this, $name)) {
                $this->$name = $value;

                return;
            }
        }

        throw new \LogicException(sprintf(
            'Bad __set(): No property named `%s` exists',
            $name
        ));
    }
}
