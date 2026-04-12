<?php
/**
 * Sprint 2: WP Cron handler for channel audit processing.
 *
 * Hook name: pw_run_channel_audit
 * Arg:       int $audit_id
 *
 * Flow:
 *   1. mark_processing
 *   2. Analyzer::analyze()   YT API + rule-based preview
 *   3. Repository::save_preview_result()
 *   4. Analyzer::analyze_with_ai()   OpenAI gpt-4o
 *   5. Repository::save_full_report()
 *   On exception  Repository::mark_error()
 *
 * @package PaywayPersonal
 */

defined( 'ABSPATH' ) || exit;

class PW_Audit_Cron {

    public const HOOK = 'pw_run_channel_audit';

    /**
     * Register WP action for cron hook.
     * Call once from payway-personal.php.
     */
    public static function register_hooks(): void {
        add_action( self::HOOK, [ self::class, 'process' ], 10, 1 );
    }

    /**
     * Process a single audit job.
     *
     * @param int $audit_id Row ID in wp_payway_channel_audits.
     */
    public static function process( int $audit_id ): void {
        // Fetch the audit row (any status  re-check inside)
        $audit = PW_Audit_Repository::find( $audit_id );
        if ( ! $audit ) {
            error_log( "[PW Audit Cron] audit #{$audit_id} not found, skipping." );
            return;
        }

        // Guard: skip if already done or processing (duplicate cron fire)
        if ( in_array( $audit['status'], [ 'done', 'processing' ], true ) ) {
            error_log( "[PW Audit Cron] audit #{$audit_id} status={$audit['status']}, skipping." );
            return;
        }

        PW_Audit_Repository::mark_processing( $audit_id );

        try {
            $analyzer = new PW_Audit_Analyzer();

            //  Step 1: YouTube API + rule-based analysis 
            $result = $analyzer->analyze( $audit['channel_url'] );

            PW_Audit_Repository::save_preview_result(
                $audit_id,
                $result['channel'],
                $result['preview'],
                $result['metrics']
            );

            //  Step 2: OpenAI full report 
            $full_report = $analyzer->analyze_with_ai( $result['ai_payload'] );

            PW_Audit_Repository::save_full_report( $audit_id, $full_report );

        } catch ( Throwable $e ) {
            error_log( "[PW Audit Cron] audit #{$audit_id} failed: " . $e->getMessage() );
            PW_Audit_Repository::mark_error( $audit_id, $e->getMessage() );
        }
    }
}
