<?php
namespace Ratchet\Wamp;
use Ratchet\ComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\Version\RFC6455\Connection;

/**
 * An extension of Ratchet\ComponentInterface to server a WAMP application
 * onMessage is replaced by various types of messages for this protocol (pub/sub or rpc)
 */
interface WampServerInterface extends ComponentInterface {
    /**
     * An RPC call has been received
     * @param \Ratchet\ConnectionInterface $conn
     * @param string                       $id The unique ID of the RPC, required to respond to
     * @param string|Topic                 $topic The topic to execute the call against
     * @param array                        $params Call parameters received from the client
     */
    function onCall(ConnectionInterface $conn, $id, $topic, array $params);

    /**
     * A function to run before a connection is added to a topic on a subscribe request.
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic
     *
     * @throws \Ratchet\Exception          if the subscription should not happen.
     */
    function onSubscribeReq(ConnectionInterface $conn, $topic);

    /**
     * A request to subscribe to a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic The topic to subscribe to
     */
    function onSubscribe(ConnectionInterface $conn, $topic);

    /**
     * A request to unsubscribe from a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic The topic to unsubscribe from
     */
    function onUnSubscribe(ConnectionInterface $conn, $topic);

    /**
     * A client is attempting to publish content to a subscribed connections on a URI
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic The topic the user has attempted to publish to
     * @param string                       $event Payload of the publish
     * @param array                        $exclude A list of session IDs the message should be excluded from (blacklist)
     * @param array                        $eligible A list of session Ids the message should be send to (whitelist)
     */
    function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible);
}