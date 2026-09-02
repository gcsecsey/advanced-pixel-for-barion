<?php
/**
 * Plugin Name: Barion test harness
 * Description: Fixtures and assertions for the Playground consent and event tests. Never shipped — .distignore keeps tests/ out of the release zip.
 * Version: 1.0.0
 * License: GPL-2.0-or-later
 *
 * A plugin rather than a set of mu-plugins because a Playground PR preview
 * installs it with one git:directory step. See tests/playground/README.md.
 */

defined( 'ABSPATH' ) || exit;

// A PHP notice or fatal must be visible in the page, not swallowed into a log
// nobody reads. This is a throwaway Playground site, never a real one.
@ini_set( 'display_errors', '1' );
error_reporting( E_ALL );

require_once __DIR__ . '/inc/recorder.php';
require_once __DIR__ . '/inc/stub-cmp.php';
require_once __DIR__ . '/inc/real-wpca.php';
require_once __DIR__ . '/inc/store.php';
require_once __DIR__ . '/inc/scenarios.php';
require_once __DIR__ . '/inc/panel.php';
require_once __DIR__ . '/inc/runner.php';
