<?php
class UrlLinker {
/**
 *  UrlLinker - facilitates turning plain text URLs into HTML links.
 *
 *  Author: Søren Løvborg
 *
 *  To the extent possible under law, Søren Løvborg has waived all copyright
 *  and related or neighboring rights to UrlLinker.
 *  http://creativecommons.org/publicdomain/zero/1.0/
 */

/*
 *  Regular expression bits used by htmlEscapeAndLinkUrls() to match URLs.
 */
private $rexScheme    = 'https?://';
// $rexScheme    = "$rexScheme|ftp://"; // Uncomment this line to allow FTP addresses.
private $rexDomain    = '(?:[-a-zA-Z0-9]{1,63}\.)+[a-zA-Z][-a-zA-Z0-9]{1,62}';
private $rexIp        = '(?:[1-9][0-9]{0,2}\.|0\.){3}(?:[1-9][0-9]{0,2}|0)';
private $rexPort      = '(:[0-9]{1,5})?';
private $rexPath      = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
private $rexQuery     = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
private $rexFragment  = '(#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
private $rexUsername  = '[^]\\\\\x00-\x20\"(),:-<>[\x7f-\xff]{1,64}';
private $rexPasswordprivate, $validTlds, $rexUrl, $rexUrlLinker;

private $rexTrailPunct= "[)'?.!,;:]"; // valid URL characters which are not part of the URL if they appear at the very end
private $rexNonUrl    = "[^-_$+.!*'(),;/?:@=&a-zA-Z0-9]"; // characters that should never appear in a URL

function __construct(){
	$this->rexPassword  = $this->rexUsername;// allow the same characters as in the username
	$this->rexUrl = "(".$this->rexScheme.")?(?:(".$this->rexUsername.")(:".$this->rexPassword.")?@)?(".$this->rexDomain."|".$this->rexIp.")(".$this->rexPort.$this->rexPath.$this->rexQuery.$this->rexFragment.")";
	$this->rexUrlLinker = "{\\b".$this->rexUrl."(?=".$this->rexTrailPunct."*(".$this->rexNonUrl."|$))}";
// $rexUrlLinker .= 'i'; // Uncomment this line to allow uppercase URL schemes (e.g. "HTTP://google.com").

/**
 *  $validTlds is an associative array mapping valid TLDs to the value true.
 *  Since the set of valid TLDs is not static, this array should be updated
 *  from time to time.
 *
 *  List source:  http://data.iana.org/TLD/tlds-alpha-by-domain.txt
 *  Last updated: 2012-09-06
 */
	$this->validTlds = array_fill_keys(explode(" ", ".ac .ad .ae .aero .af .ag .ai .al .am .an .ao .aq .ar .arpa .as .asia .at .au .aw .ax .az .ba .bb .bd .be .bf .bg .bh .bi .biz .bj .bm .bn .bo .br .bs .bt .bv .bw .by .bz .ca .cat .cc .cd .cf .cg .ch .ci .ck .cl .cm .cn .co .com .coop .cr .cu .cv .cw .cx .cy .cz .de .dj .dk .dm .do .dz .ec .edu .ee .eg .er .es .et .eu .fi .fj .fk .fm .fo .fr .ga .gb .gd .ge .gf .gg .gh .gi .gl .gm .gn .gov .gp .gq .gr .gs .gt .gu .gw .gy .hk .hm .hn .hr .ht .hu .id .ie .il .im .in .info .int .io .iq .ir .is .it .je .jm .jo .jobs .jp .ke .kg .kh .ki .km .kn .kp .kr .kw .ky .kz .la .lb .lc .li .lk .lr .ls .lt .lu .lv .ly .ma .mc .md .me .mg .mh .mil .mk .ml .mm .mn .mo .mobi .mp .mq .mr .ms .mt .mu .museum .mv .mw .mx .my .mz .na .name .nc .ne .net .nf .ng .ni .nl .no .np .nr .nu .nz .om .org .pa .pe .pf .pg .ph .pk .pl .pm .pn .post .pr .pro .ps .pt .pw .py .qa .re .ro .rs .ru .rw .sa .sb .sc .sd .se .sg .sh .si .sj .sk .sl .sm .sn .so .sr .st .su .sv .sx .sy .sz .tc .td .tel .tf .tg .th .tj .tk .tl .tm .tn .to .tp .tr .travel .tt .tv .tw .tz .ua .ug .uk .us .uy .uz .va .vc .ve .vg .vi .vn .vu .wf .ws .xn--0zwm56d .xn--11b5bs3a9aj6g .xn--3e0b707e .xn--45brj9c .xn--80akhbyknj4f .xn--80ao21a .xn--90a3ac .xn--9t4b11yi5a .xn--clchc0ea0b2g2a9gcd .xn--deba0ad .xn--fiqs8s .xn--fiqz9s .xn--fpcrj9c3d .xn--fzc2c9e2c .xn--g6w251d .xn--gecrj9c .xn--h2brj9c .xn--hgbk6aj7f53bba .xn--hlcj6aya9esc7a .xn--j6w193g .xn--jxalpdlp .xn--kgbechtv .xn--kprw13d .xn--kpry57d .xn--lgbbat1ad8j .xn--mgb9awbf .xn--mgbaam7a8h .xn--mgbayh7gpa .xn--mgbbh1a71e .xn--mgbc0a9azcg .xn--mgberp4a5d4ar .xn--o3cw4h .xn--ogbpf8fl .xn--p1ai .xn--pgbs0dh .xn--s9brj9c .xn--wgbh1c .xn--wgbl6a .xn--xkc2al3hye2a .xn--xkc2dl3a5ee0h .xn--yfro4i67o .xn--ygbi2ammx .xn--zckzah .xxx .ye .yt .za .zm .zw"), true);
}
/**
 *  Transforms plain text into valid HTML, escaping special characters and
 *  turning URLs into links.
 */
function htmlEscapeAndLinkUrls($text)
{
    $html = '';

    $position = 0;
    while (preg_match($this->rexUrlLinker, $text, $match, PREG_OFFSET_CAPTURE, $position))
    {
        list($url, $urlPosition) = $match[0];

        // Add the text leading up to the URL.
        $html .= htmlspecialchars(substr($text, $position, $urlPosition - $position));

        $scheme      = $match[1][0];
        $username    = $match[2][0];
        $password    = $match[3][0];
        $domain      = $match[4][0];
        $afterDomain = $match[5][0]; // everything following the domain
        $port        = $match[6][0];
        $path        = $match[7][0];

        // Check that the TLD is valid or that $domain is an IP address.
        $tld = strtolower(strrchr($domain, '.'));
        if (preg_match('{^\.[0-9]{1,3}$}', $tld) || isset($this->validTlds[$tld]))
        {
            // Do not permit implicit scheme if a password is specified, as
            // this causes too many errors (e.g. "my email:foo@example.org").
            if (!$scheme && $password)
            {
                $html .= htmlspecialchars($username);

                // Continue text parsing at the ':' following the "username".
                $position = $urlPosition + strlen($username);
                continue;
            }

            if (!$scheme && $username && !$password && !$afterDomain)
            {
                // Looks like an email address.
                $completeUrl = "mailto:$url";
                $linkText = $url;
            }
            else
            {
                // Prepend http:// if no scheme is specified
                $completeUrl = $scheme ? $url : "http://$url";
                $linkText = "$domain$port$path";
            }

            $linkHtml = '<a href="' . htmlspecialchars($completeUrl) . '">'
                . htmlspecialchars($linkText)
                . '</a>';

            // Cheap e-mail obfuscation to trick the dumbest mail harvesters.
            $linkHtml = str_replace('@', '&#64;', $linkHtml);

            // Add the hyperlink.
            $html .= $linkHtml;
        }
        else
        {
            // Not a valid URL.
            $html .= htmlspecialchars($url);
        }

        // Continue text parsing from after the URL.
        $position = $urlPosition + strlen($url);
    }

    // Add the remainder of the text.
    $html .= htmlspecialchars(substr($text, $position));
    return $html;
}

/**
 * Turns URLs into links in a piece of valid HTML/XHTML.
 *
 * Beware: Never render HTML from untrusted sources. Rendering HTML provided by
 * a malicious user can lead to system compromise through cross-site scripting.
 */
function linkUrlsInTrustedHtml($html)
{
    $reMarkup = '{</?([a-z]+)([^"\'>]|"[^"]*"|\'[^\']*\')*>|&#?[a-zA-Z0-9]+;|$}';

    $insideAnchorTag = false;
    $position = 0;
    $result = '';

    // Iterate over every piece of markup in the HTML.
    while (true)
    {
        preg_match($reMarkup, $html, $match, PREG_OFFSET_CAPTURE, $position);

        list($markup, $markupPosition) = $match[0];

        // Process text leading up to the markup.
        $text = substr($html, $position, $markupPosition - $position);

        // Link URLs unless we're inside an anchor tag.
        if (!$insideAnchorTag) $text = $this->htmlEscapeAndLinkUrls($text);

        $result .= $text;

        // End of HTML?
        if ($markup === '') break;

        // Check if markup is an anchor tag ('<a>', '</a>').
        if ($markup[0] !== '&' && $match[1][0] === 'a')
            $insideAnchorTag = ($markup[1] !== '/');

        // Pass markup through unchanged.
        $result .= $markup;

        // Continue after the markup.
        $position = $markupPosition + strlen($markup);
    }
    return $result;
}
}
$test = new UrlLinker();
$text = <<<EOD
Here's an e-mail-address:bob+test@example.org. Here's an authenticated URL: http://skroob:12345@example.com.
Here are some URLs:
stackoverflow.com/questions/1188129/pregreplace-to-detect-html-php
Here's the answer: http://www.google.com/search?rls=en&q=42&ie=utf-8&oe=utf-8&hl=en. What was the question?
A quick look at 'http://en.wikipedia.org/wiki/URI_scheme#Generic_syntax' is helpful.
There is no place like 127.0.0.1! Except maybe http://news.bbc.co.uk/1/hi/england/surrey/8168892.stm?
Ports: 192.168.0.1:8080, https://example.net:1234/.
Beware of Greeks bringing internationalized top-level domains (xn--hxajbheg2az3al.xn--jxalpdlp).
10.000.000.000 is not an IP-address. Nor is this.a.domain.

<script>alert('Remember kids: Say no to XSS-attacks! Always HTML escape untrusted input!');</script>

EOD;

print(nl2br($test->htmlEscapeAndLinkUrls($text)));
?>
</p>
<!-- HTML input -->
<?php
$html = <<<EOD
<p>Send me an <a href="bob@example.com">e-mail</a> at bob@example.com.</p>
<p>This is already a link: <a href="http://google.com">http://google.com</a></p>
<p title='10>20'>Tricky markup...</p>
EOD;
print($test->linkUrlsInTrustedHtml($html));

?>
