<?php

namespace MgKurentoClient;

use React\Promise\Promise;

class WebRtcEndpoint extends MediaElement implements Interfaces\WebRtcEndpoint
{
    public function generateOffer(): Promise
    {
        return $this->remoteInvoke('generateOffer', [])
            ->then(function($result) {
              return $result['value'];
            });
    }

    public function getLocalSessionDescriptor()
    {
        // TODO: Implement getLocalSessionDescriptor() method.
    }

    public function getRemoteSessionDescriptor()
    {
        // TODO: Implement getRemoteSessionDescriptor() method.
    }

    public function processAnswer($answer): Promise
    {
        return $this->remoteInvoke('processAnswer', ['answer' => $answer]);
    }

    public function processOffer($offer): Promise
    {
        return $this->remoteInvoke('processOffer', ['offer' => $offer]);
    }

    public function gatherCandidates(): Promise
    {
        return $this->remoteInvoke('gatherCandidates', []);
    }

    public function addIceCandidate($candidate): Promise
    {
        return $this->remoteInvoke('addIceCandidate', ['candidate' => $candidate]);
    }
}
