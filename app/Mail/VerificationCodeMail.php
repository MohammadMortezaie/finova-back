<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;

class VerificationCodeMail extends Mailable
{
    public function __construct(public User $user, public string $code) {}

    public function build(): self
    {
        $escapedName = e($this->user->name);
        $escapedCode = e($this->code);

        $html = <<<HTML
<!doctype html>
<html>
<head>
  <title>Finova verification code</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f6;font-family:Arial,Helvetica,sans-serif;color:#1c2520;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #dfe7e2;">
          <tr>
            <td style="padding:28px 28px 18px;background:#111814;color:#ffffff;">
              <div style="font-size:14px;color:#73d99f;font-weight:700;letter-spacing:.4px;">FINOVA</div>
              <h1 style="margin:10px 0 0;font-size:26px;line-height:32px;font-weight:800;">Verify your email</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:28px;">
              <p style="margin:0 0 14px;font-size:16px;line-height:24px;">Hi {$escapedName},</p>
              <p style="margin:0 0 22px;font-size:16px;line-height:24px;color:#526158;">Use this 6-digit code to finish creating your account.</p>
              <div style="text-align:center;margin:26px 0;">
                <div style="display:inline-block;padding:18px 28px;border-radius:14px;background:#eefaf2;border:1px solid #bce9ca;color:#111814;font-size:34px;font-weight:800;letter-spacing:8px;">{$escapedCode}</div>
              </div>
              <p style="margin:0;font-size:14px;line-height:22px;color:#6a766f;">This code expires in 15 minutes. If you did not request it, you can ignore this email.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

        return $this
            ->subject('Your Finova verification code')
            ->html($html);
    }
}
