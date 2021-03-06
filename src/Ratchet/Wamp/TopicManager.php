<?php
namespace Ratchet\Wamp;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServerInterface;

class TopicManager implements WsServerInterface, WampServerInterface {
    /**
     * @var WampServerInterface
     */
    protected $app;

    /**
     * @var array
     */
    protected $topicLookup = array();

    public function __construct(WampServerInterface $app) {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn) {
        $conn->WAMP->subscriptions = new \SplObjectStorage;
        $this->app->onOpen($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        $this->app->onCall($conn, $id, $this->getTopic($topic), $params);
    }

    /**
     * {@inheritdoc}
     */
    public function onSubscribeReq(ConnectionInterface $conn, $topic) {}

    /**
     * {@inheritdoc}
     */
    public function onSubscribe(ConnectionInterface $conn, $topic) {
        $topicObj = $this->getTopic($topic);

        if ($conn->WAMP->subscriptions->contains($topicObj)) {
            return;
        }

        try {
            $this->app->onSubscribeReq($conn, $topicObj);
        } catch (Exception $e) {
            // No action taken according to WAMP v1 protocol.
            // @ref http://wamp.ws/spec#subscribe_message
            return;
        }

        $this->topicLookup[$topic]->add($conn);
        $conn->WAMP->subscriptions->attach($topicObj);
        $this->app->onSubscribe($conn, $topicObj);
    }

    /**
     * {@inheritdoc}
     */
    public function onUnsubscribe(ConnectionInterface $conn, $topic) {
        $topicObj = $this->getTopic($topic);

        if ($conn->WAMP->subscriptions->contains($topicObj)) {
            $conn->WAMP->subscriptions->detach($topicObj);
        } else {
            return;
        }

        $this->topicLookup[$topic]->remove($conn);
        $this->app->onUnsubscribe($conn, $topicObj);
    }

    /**
     * {@inheritdoc}
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        $this->app->onPublish($conn, $this->getTopic($topic), $event, $exclude, $eligible);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn) {
        $this->app->onClose($conn);

        foreach ($this->topicLookup as $topic) {
            $topic->remove($conn);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->app->onError($conn, $e);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProtocols() {
        if ($this->app instanceof WsServerInterface) {
            return $this->app->getSubProtocols();
        }

        return array();
    }

    /**
     * @param string
     * @return Topic
     */
    protected function getTopic($topic) {
        if (!array_key_exists($topic, $this->topicLookup)) {
            $this->topicLookup[$topic] = new Topic($topic);
        }

        return $this->topicLookup[$topic];
    }
}