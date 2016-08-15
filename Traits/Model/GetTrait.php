<?php

namespace Lthrt\CarveBundle\Traits\Model;

// Copied from LthrtEntityBundle
// Removed if that Bundle is used

/**
 * GetSet Trait.
 *
 * For discussion see: http://www.epixa.com/2010/05/the-best-models-are-easy-models.html
 */
trait GetTrait
{
    /**
     * This is not meant to be used alone.  Use GetSetTrait.
     *
     * Map a call to get a property to its corresponding accessor if it exists.
     * Otherwise, get the property directly.
     *
     * Ignore any properties that begin with an underscore so not all of our
     * protected properties are exposed.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws \LogicException If no accessor/property exists by that name
     */
    public function __get($name)
    {
        if ('_' != $name[0]) {
            $accessor = 'get' . ucfirst($name);
            if (method_exists($this, $accessor)) {
                return $this->$accessor();
            }

            if (property_exists($this, $name)) {
                return $this->$name;
            }
        }

        throw new \LogicException(sprintf(
            'Bad __get(): No property named `%s` exists',
            $name
        ));
    }
}
