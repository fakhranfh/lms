<?php

namespace App\Enums;

enum ProctorEventType: string
{
    case TabSwitch = 'tab_switch';
    case WindowBlur = 'window_blur';
    case MultipleFaces = 'multiple_faces';
    case NoFaceDetected = 'no_face_detected';
    case FaceMismatch = 'face_mismatch';
    case CopyPaste = 'copy_paste';
    case RightClick = 'right_click';
    case DevtoolsOpened = 'devtools_opened';
    case FullscreenExit = 'fullscreen_exit';
    case NetworkActivityDetected = 'network_activity_detected';
    case UnauthorizedAppDetected = 'unauthorized_app_detected';
    case ReadingSuspected = 'reading_suspected';
}
