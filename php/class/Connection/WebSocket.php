<?php
namespace Connection;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebSocket implements MessageComponentInterface {
    protected $clients;
	private $names;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
		$this->names = Array();
    }

    public function onOpen(ConnectionInterface $conn) {
        // Save the client
        $this->clients->attach($conn);		
        echo "New connection! ({$conn->resourceId})\n";		
    }

    public function onMessage(ConnectionInterface $from, $msg) { 		
        echo sprintf('Received message "%s"' . "\n",$msg);					
		
		$jsonData = json_decode($msg, true);	

		if($jsonData['data'] == '') {
			return;
		}
		
		$sendTime = date('H:i', strtotime('now'));
		
		if($jsonData['type'] == 'message') {			
			$sendMessage = $jsonData['data'];
			// send message for all
			$this->broadcast($this->buildJSON('message', $sendMessage, $this->getName($from), $sendTime), $from);
			// send message only from
			$this->sendMessageForClient($from, $this->buildJSON('message', $sendMessage, 'Me', $sendTime));
			
		} else if($jsonData['type'] == 'name') {
			$this->addName($from, $jsonData['data']);
			// notify the clients who is on
			$this->notifyUsersOn();
			// notify that this user is on
			$this->broadcast($this->buildJSON('message', 'The user '.$this->getName($from).' has connected', 'Server', $sendTime), null);
		}
    }

    public function onClose(ConnectionInterface $conn) {
		$sendTime = date('H:i', strtotime('now'));
		$this->broadcast($this->buildJSON('message', 'The user '.$this->getName($from).' has disconnected', 'Server', $sendTime), null);
		
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
		$this->removeName($conn);
		
		$this->notifyUsersOn();

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }	

	private function notifyUsersOn() {
		$this->broadcast($this->buildJSON('on', $this->names, null, null), null);
	}
	
	private function broadcast($msg, $exclude) {
		foreach ($this->clients as $client) {
			if($exclude == null || $client !== $exclude) {
				$client->send($msg);		
			}
		}
	}
	
	private function sendMessageForClient($to, $msg) {
		$to->send($msg);
	}
	
	private function addName($client, $name) {
		$this->names[$client->resourceId] = $name;		
	}
	
	private function removeName($client) {
		unset($this->names[$client->resourceId]);		
	}
	
	private function getName($client) {
		return $this->names[$client->resourceId];
	}
	
	/*
		Build the JSON
		type = message and on
		data = your data.
	*/
	private function buildJSON($type, $data, $fromName, $sendTime) {	
		$result;
		if($type == 'on') {			
			$d = '[';
			$comma = '';
			foreach ($data as $name) {
				$d .= $comma;
				$d .= '"'.$name.'"';
				$comma = ',';
			}
			$d .= ']';	
			$result = '{"type": "'.$type.'", "data": '.$d.'}';
		} else {
			$result = '{"type": "'.$type.'", "data": "'.$data.'", "fromName": "'.$fromName.'", "sendTime": "'.$sendTime.'"}';
		}	
		return $result;
	}
}