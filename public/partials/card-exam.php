<?php
/**
 * Shared exam card markup.
 *
 * Expects two variables in scope (provided by examhub_render_exam_card()):
 *   $exam — standardized card-data array from Examhub_Query::get_card_data()
 *   $atts — display options: show_image, show_stats (booleans)
 *
 * @package    Examhub
 * @subpackage Examhub/public/partials
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$show_image = ! empty( $atts['show_image'] );
$show_stats = ! empty( $atts['show_stats'] );
?>
<div class="examhub-card<?php echo $exam['featured'] ? ' examhub-card--featured' : ''; ?>">

	<?php if ( $show_image && $exam['thumbnail'] ) : ?>
		<div class="examhub-card__image">
			<img src="<?php echo esc_url( $exam['thumbnail'] ); ?>" alt="<?php echo esc_attr( $exam['title'] ); ?>" loading="lazy" />
			<?php if ( $exam['featured'] ) : ?>
				<span class="examhub-card__badge"><?php esc_html_e( 'ویژه', 'examhub' ); ?></span>
			<?php endif; ?>
		</div>
	<?php elseif ( $exam['featured'] ) : ?>
		<span class="examhub-card__badge examhub-card__badge--floating"><?php esc_html_e( 'ویژه', 'examhub' ); ?></span>
	<?php endif; ?>

	<div class="examhub-card__body">
		<h3 class="examhub-card__title"><?php echo esc_html( $exam['title'] ); ?></h3>

		<?php if ( $exam['meta_line'] ) : ?>
			<p class="examhub-card__meta"><?php echo esc_html( $exam['meta_line'] ); ?></p>
		<?php endif; ?>

		<?php if ( $exam['year'] || $exam['field'] ) : ?>
			<div class="examhub-card__chips">
				<?php if ( $exam['year'] ) : ?>
					<span class="examhub-card__chip examhub-card__chip--year"><?php echo esc_html( $exam['year'] ); ?></span>
				<?php endif; ?>
				<?php if ( $exam['field'] ) : ?>
					<span class="examhub-card__chip examhub-card__chip--type"><?php echo esc_html( $exam['field'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $show_stats ) : ?>
			<p class="examhub-card__stats">
				<svg class="examhub-card__stats-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="7 10 12 15 17 10" /><line x1="12" y1="15" x2="12" y2="3" /></svg><span class="examhub-card__stats-number"><?php echo esc_html( number_format_i18n( $exam['total_downloads'] ) ); ?></span>
				<?php esc_html_e( 'دانلود', 'examhub' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<div class="examhub-card__actions">

		<div class="examhub-card__downloads">
			<?php if ( $exam['questions']['available'] ) : ?>
				<a href="<?php echo esc_url( $exam['questions']['url'] ); ?>" class="examhub-btn examhub-btn--questions">
					<?php esc_html_e( 'دانلود سوالات', 'examhub' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $exam['answers']['available'] ) : ?>
				<a href="<?php echo esc_url( $exam['answers']['url'] ); ?>" class="examhub-btn examhub-btn--answers">
					<?php esc_html_e( 'دانلود پاسخنامه', 'examhub' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $exam['questions']['view_url'] || $exam['answers']['view_url'] ) : ?>
			<div class="examhub-card__views">
				<?php if ( $exam['questions']['view_url'] ) : ?>
					<a href="<?php echo esc_url( $exam['questions']['view_url'] ); ?>" class="examhub-card__view-link" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'نمایش سوالات', 'examhub' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( $exam['answers']['view_url'] ) : ?>
					<a href="<?php echo esc_url( $exam['answers']['view_url'] ); ?>" class="examhub-card__view-link" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'نمایش پاسخنامه', 'examhub' ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>
</div>
