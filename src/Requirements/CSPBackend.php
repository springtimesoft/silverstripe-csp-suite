<?php

namespace Springtimesoft\CSPSuite\Requirements;

use Silverstripe\CSP\NonceGenerator;
use Silverstripe\CSP\Requirements\CSPBackend as BaseCSPBackend;
use SilverStripe\Dev\Deprecation;
use SilverStripe\View\HTML;

/**
 * A patched version of silverstripe-csp's CSPBackend implementation (which itself is a patched version of the core
 * Requirements Backend implementation) that expands nonce support to CSS/JS files rather than limiting it to inline
 * scripts and styles. This is particularly relevant when using strict-dynamic, which requires _all_ scripts to be
 * covered by a nonce.
 *
 * @see \Silverstripe\CSP\Requirements\CSPBackend
 */
class CSPBackend extends BaseCSPBackend
{
    /**
     * Good old monolithic functions...
     * EDITED: A comment should sit above anything changed mentioning what changed and why
     *         it will start with `EDITED`
     *
     * @inheritDoc
     */
    public function includeInHTML($content)
    {
        if (func_num_args() > 1) {
            Deprecation::notice(
                '5.0',
                '$templateFile argument is deprecated. includeInHTML takes a sole $content parameter now.'
            );
            $content = func_get_arg(1);
        }

        // Skip if content isn't injectable, or there is nothing to inject
        $tagsAvailable = preg_match('#</head\b#', $content);
        $hasFiles = $this->css || $this->javascript || $this->customCSS || $this->customScript || $this->customHeadTags;
        if (!$tagsAvailable || !$hasFiles) {
            return $content;
        }
        $requirements = '';
        $jsRequirements = '';

        // Combine files - updates $this->javascript and $this->css
        $this->processCombinedFiles();

        // Script tags for js links
        foreach ($this->getJavascript() as $file => $attributes) {
            // Build html attributes
            $htmlAttributes = [
                'type' => isset($attributes['type']) ? $attributes['type'] : "application/javascript",
                'src' => $this->pathForFile($file),
                // EDITED: We added in the nonce here (CSP Suite enhancement)
                'nonce' => NonceGenerator::get(),
            ];
            if (!empty($attributes['async'])) {
                $htmlAttributes['async'] = 'async';
            }
            if (!empty($attributes['defer'])) {
                $htmlAttributes['defer'] = 'defer';
            }
            if (!empty($attributes['integrity'])) {
                $htmlAttributes['integrity'] = $attributes['integrity'];
            }
            if (!empty($attributes['crossorigin'])) {
                $htmlAttributes['crossorigin'] = $attributes['crossorigin'];
            }
            $jsRequirements .= HTML::createTag('script', $htmlAttributes);
            $jsRequirements .= "\n";
        }

        // Add all inline JavaScript *after* including external files they might rely on
        foreach ($this->getCustomScripts() as $script) {
            $src = $script['src'];
            $jsRequirements .= HTML::createTag(
                'script',
                // EDITED: We added in the nonce here
                [
                    'type' => 'application/javascript',
                    'nonce' => $script['nonce'],
                ],
                /**
                 * EDITED: We added in the script src here (was just `$script` before)
                 *         @see CSPBackend::getCustomScripts
                 */
                "//<![CDATA[\n{$src}\n//]]>"
            );
            $jsRequirements .= "\n";
        }

        // CSS file links
        foreach ($this->getCSS() as $file => $params) {
            $htmlAttributes = [
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => $this->pathForFile($file),
                // EDITED: We added in the nonce here (CSP Suite enhancement)
                'nonce' => NonceGenerator::get(),
            ];
            if (!empty($params['media'])) {
                $htmlAttributes['media'] = $params['media'];
            }
            if (!empty($params['integrity'])) {
                $htmlAttributes['integrity'] = $params['integrity'];
            }
            if (!empty($params['crossorigin'])) {
                $htmlAttributes['crossorigin'] = $params['crossorigin'];
            }
            $requirements .= HTML::createTag('link', $htmlAttributes);
            $requirements .= "\n";
        }

        // Literal custom CSS content
        foreach ($this->getCustomCSS() as $css) {
            /**
             * EDITED: We added in the script src (was just `$css` before) and nonce
             *         @see CSPBackend::getCustomCSS
             */
            $requirements .= HTML::createTag(
                'style',
                [
                    'type' => 'text/css',
                    'nonce' => $css['nonce'],
                ],
                "\n{$css['src']}\n"
            );
            $requirements .= "\n";
        }

        foreach ($this->getCustomHeadTags() as $customHeadTag) {
            $requirements .= "{$customHeadTag}\n";
        }

        // Inject CSS  into body
        $content = $this->insertTagsIntoHead($requirements, $content);

        // Inject scripts
        if ($this->getForceJSToBottom()) {
            $content = $this->insertScriptsAtBottom($jsRequirements, $content);
        } elseif ($this->getWriteJavascriptToBody()) {
            $content = $this->insertScriptsIntoBody($jsRequirements, $content);
        } else {
            $content = $this->insertTagsIntoHead($jsRequirements, $content);
        }
        return $content;
    }
}
