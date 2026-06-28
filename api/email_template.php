<?php
/**
 * Shared, on-brand email layout — used by BOTH the real notification/confirmation
 * emails (submit.php) and the admin "send test" feature (admin.php), so a test
 * always looks exactly like the real thing.
 *
 * Email-safe: table-based, inline styles, a PNG logo (SVG doesn't render in most
 * mail clients), serif headings via Georgia (a Newsreader stand-in).
 */
require_once __DIR__ . '/config.php';

function eml(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** A labelled field row for the email body. $value may contain safe HTML. */
function email_row(string $label, string $value): string
{
    return '<p style="margin:0 0 14px;">'
        . '<span style="display:block;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#906f6b;margin-bottom:2px;">' . eml($label) . '</span>'
        . '<span style="font-size:16px;color:#1c1c18;">' . $value . '</span></p>';
}

/** A red call-to-action button. */
function email_button(string $text, string $url): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 4px;">'
        . '<tr><td style="border-radius:8px;background:#b20112;">'
        . '<a href="' . eml($url) . '" style="display:inline-block;padding:12px 24px;font-family:Arial,Helvetica,sans-serif;'
        . 'font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:8px;">' . eml($text) . '</a>'
        . '</td></tr></table>';
}

/** Full branded HTML email. $inner is trusted, pre-built HTML. */
function email_layout(string $heading, string $inner): string
{
    $logo = rtrim(SITE_URL, '/') . '/logo-email.png';
    return '<!DOCTYPE html><html lang="en"><head>'
        . '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="color-scheme" content="light"></head>'
        . '<body style="margin:0;padding:0;background:#fdf9f3;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fdf9f3;">'
        . '<tr><td align="center" style="padding:28px 16px;">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">'

        // ---- white card ----
        . '<tr><td style="background:#ffffff;border:1px solid #e5bdb9;border-radius:16px;overflow:hidden;">'

        // logo
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td align="center" style="padding:30px 24px 14px;">'
        . '<img src="' . $logo . '" alt="The Heart of Jerome" width="230" style="display:block;width:230px;max-width:72%;height:auto;border:0;">'
        . '</td></tr></table>'

        // red accent rule
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td style="padding:0 28px;"><div style="height:3px;line-height:3px;font-size:3px;background:#b20112;border-radius:2px;">&nbsp;</div></td>'
        . '</tr></table>'

        // body
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td style="padding:22px 28px 30px;font-family:Arial,Helvetica,sans-serif;color:#1c1c18;font-size:16px;line-height:1.6;">'
        . '<h1 style="font-family:Georgia,\'Times New Roman\',serif;font-style:italic;color:#b20112;font-size:25px;margin:0 0 16px;">' . eml($heading) . '</h1>'
        . $inner
        . '</td></tr></table>'

        . '</td></tr>'

        // ---- footer ----
        . '<tr><td align="center" style="padding:18px 12px 4px;font-family:Arial,Helvetica,sans-serif;color:#906f6b;font-size:12px;line-height:1.5;">'
        . eml(SITE_NAME) . ' &middot; America250 &middot; Honoring our heritage, cultivating our future.'
        . '</td></tr>'

        . '</table></td></tr></table></body></html>';
}
