<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Nested <form> tags are invalid HTML — browsers "recover" by silently
 * breaking the outer form's boundary, which can leave a submit button
 * physically inside the form in the Blade source but NOT associated with
 * any form once the browser actually parses it. Caught for real on the
 * Institute edit page: its "Reset Password" mini-form sat directly inside
 * the main update form, and clicking "Save Changes" (which came after it in
 * the markup) silently did nothing — no request, no error, nothing — since
 * every Laravel feature test hits the update route directly and never
 * parses real browser DOM, so nothing in the whole suite could ever have
 * caught this. This is a pure static check for that one specific mistake,
 * not a substitute for actually loading pages in a browser.
 */
class BladeFormNestingTest extends TestCase
{
    public function test_no_blade_view_contains_a_form_nested_inside_another_form(): void
    {
        $offenders = [];

        foreach ($this->allBladeFiles() as $path) {
            $maxDepth = $this->maxFormNestingDepth(file_get_contents($path));

            if ($maxDepth > 1) {
                $offenders[] = "{$path} (nesting depth {$maxDepth})";
            }
        }

        $this->assertEmpty($offenders, "Nested <form> tags found:\n".implode("\n", $offenders));
    }

    public function test_no_reusable_form_partial_contains_its_own_form_tag(): void
    {
        // Every "_form.blade.php"-style partial in this app is always
        // @included from inside a page that already wraps it in a <form> —
        // if one of these ever grows a <form> of its own, that's the same
        // nesting bug by construction, regardless of what the including
        // page looks like.
        $offenders = [];
        $this->walkBladeFiles(resource_path('views'), function (string $path) use (&$offenders) {
            if (basename($path) === '_form.blade.php' && str_contains(file_get_contents($path), '<form')) {
                $offenders[] = $path;
            }
        });

        $this->assertEmpty($offenders, "Reusable form partials containing their own <form> tag:\n".implode("\n", $offenders));
    }

    private function maxFormNestingDepth(string $content): int
    {
        preg_match_all('/<form\b|<\/form>/', $content, $matches);

        $depth = 0;
        $maxDepth = 0;

        foreach ($matches[0] as $tag) {
            if (str_starts_with($tag, '<form')) {
                $depth++;
                $maxDepth = max($maxDepth, $depth);
            } else {
                $depth--;
            }
        }

        return $maxDepth;
    }

    /**
     * @return array<int, string>
     */
    private function allBladeFiles(): array
    {
        $files = [];
        $this->walkBladeFiles(resource_path('views'), function (string $path) use (&$files) {
            $files[] = $path;
        });

        return $files;
    }

    private function walkBladeFiles(string $directory, callable $callback): void
    {
        foreach (scandir($directory) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.'/'.$entry;

            if (is_dir($path)) {
                $this->walkBladeFiles($path, $callback);
            } elseif (str_ends_with($entry, '.blade.php')) {
                $callback($path);
            }
        }
    }
}
