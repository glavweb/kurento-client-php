<?php

namespace MgKurentoClient;

use React\Promise\PromiseInterface;

class WebRtcEndpoint extends MediaElement implements Interfaces\WebRtcEndpoint
{
    public function generateOffer(): PromiseInterface
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

    public function processAnswer($answer): PromiseInterface
    {
        return $this->remoteInvoke('processAnswer', ['answer' => $answer]);
    }

    public function processOffer($offer): PromiseInterface
    {
        return $this->remoteInvoke('processOffer', ['offer' => $offer]);
    }

    public function gatherCandidates(): PromiseInterface
    {
        return $this->remoteInvoke('gatherCandidates', []);
    }

    public function addIceCandidate($candidate): PromiseInterface
    {
        return $this->remoteInvoke('addIceCandidate', ['candidate' => $candidate]);
    }
}
