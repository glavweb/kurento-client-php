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

    public function setMaxVideoSendBandwidth(int $value): Promise
    {
        return $this->remoteInvoke('setMaxVideoSendBandwidth', ['maxVideoSendBandwidth' => $value]);
    }

    public function setMinVideoSendBandwidth(int $value): Promise
    {
        return $this->remoteInvoke('setMinVideoSendBandwidth', ['minVideoSendBandwidth' => $value]);
    }

    public function setMaxVideoRecvBandwidth(int $value): Promise
    {
        return $this->remoteInvoke('setMaxVideoRecvBandwidth', ['maxVideoRecvBandwidth' => $value]);
    }

    public function setMinVideoRecvBandwidth(int $value): Promise
    {
        return $this->remoteInvoke('setMinVideoRecvBandwidth', ['minVideoRecvBandwidth' => $value]);
    }

    public function setMaxAudioSendBandwidth(int $value): Promise
    {
        return $this->remoteInvoke('setMaxAudioSendBandwidth', ['maxAudioSendBandwidth' => $value]);
    }

    public function setMinAudioSendBandwidth(int $value): Promise
    {
        return $this->remoteInvoke('setMinAudioSendBandwidth', ['minAudioSendBandwidth' => $value]);
    }

    public function setMaxAudioRecvBandwidth(int $value): Promise
    {
        return $this->remoteInvoke('setMaxAudioRecvBandwidth', ['maxAudioRecvBandwidth' => $value]);
    }

    public function setMinAudioRecvBandwidth(int $value): Promise
    {
        return $this->remoteInvoke('setMinAudioRecvBandwidth', ['minAudioRecvBandwidth' => $value]);
    }
}
