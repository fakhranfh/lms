<?php

namespace App\Enums;

enum ProctorSnapshotType: string
{
    case Webcam = 'webcam';
    case Screen = 'screen';
    case Recording = 'recording';
}
