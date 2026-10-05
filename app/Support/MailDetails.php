<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * A compact label → value table for notification emails (inline styles, since
 * email clients ignore most CSS). Empty values are skipped. Use as a
 * MailMessage line: ->line(MailDetails::table([...])).
 */
class MailDetails
{
    /** @param array<string, string|int|float|null> $rows */
    public static function table(array $rows): HtmlString
    {
        $html = '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" '
            .'style="border:1px solid #ece7da;border-radius:10px;border-collapse:separate;margin:4px 0 20px;background:#fbfaf6;">';
        $first = true;
        foreach ($rows as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $border = $first ? '' : 'border-top:1px solid #ece7da;';
            $html .= '<tr>'
                .'<td style="'.$border.'padding:9px 14px;color:#8a8370;font-size:13px;width:38%;vertical-align:top;">'.e($label).'</td>'
                .'<td style="'.$border.'padding:9px 14px;color:#1f2937;font-size:14px;font-weight:600;">'.e((string) $value).'</td>'
                .'</tr>';
            $first = false;
        }

        return new HtmlString($html.'</table>');
    }
}
