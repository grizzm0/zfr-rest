<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ZfrRest\Mvc;

use Traversable;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Hydrator\HydratorPluginManager;
use Zend\View\Model\ModelInterface;
use ZfrRest\Resource\ResourceInterface;
use ZfrRest\View\Model\ResourceModel;

/**
 * This listener is responsible to create the resource model
 *
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 * @licence MIT
 */
class CreateResourceModelListener extends AbstractListenerAggregate
{
    /**
     * @var HydratorPluginManager
     */
    protected $hydratorPluginManager;

    /**
     * Constructor
     *
     * @param HydratorPluginManager $hydratorPluginManager
     */
    public function __construct(HydratorPluginManager $hydratorPluginManager)
    {
        $this->hydratorPluginManager = $hydratorPluginManager;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'createResourceModel'), -40);
    }

    /**
     * Create payload and generate a JsonModel
     *
     * @internal
     * @param  MvcEvent $event
     * @return void
     */
    public function createResourceModel(MvcEvent $event)
    {
        // Do nothing if a Model has already been returned, or if we don't have any resource
        if (($result = $event->getResult() instanceof ModelInterface)
            || !$event->getParam('resource') instanceof ResourceInterface
        ) {
            return;
        }

        $resource = $event->getParam('resource');

        // If we have a traversable (usually a paginator), we extract each element individually
        if ($resource->isCollection()) {
            // Get the collection hydrator
            $collectionMetadata = $resource->getMetadata()->getCollectionMetadata();
            $hydrator           = $this->hydratorPluginManager->get($collectionMetadata->getHydratorName());
        } else {
            $metadata = $resource->getMetadata();
            $hydrator = $this->hydratorPluginManager->get($metadata->getHydratorName());
        }

        $resourceModel = new ResourceModel($resource, $hydrator);

        $event->setViewModel($resourceModel);
        $event->setResult($resourceModel);
    }
}
