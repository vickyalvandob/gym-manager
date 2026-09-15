<?php

namespace App\Support;

use App\Models\Member;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class MemberQrCode
{
    public function dataUrl(Member $member): ?string
    {
        if (! is_string($member->portal_qr_token)) {
            return null;
        }

        $payload = implode(':', [
            'GYMLO',
            $member->gym->slug,
            $member->member_number,
            $member->portal_qr_token,
        ]);
        $renderer = new ImageRenderer(
            new RendererStyle(320, 2),
            new SvgImageBackEnd,
        );
        $svg = (new Writer($renderer))->writeString($payload);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
