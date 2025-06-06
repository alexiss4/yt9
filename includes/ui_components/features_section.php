<?php
/**
 * Reusable UI component for displaying a grid of features.
 *
 * This component generates a responsive grid layout to showcase a list of features.
 * Each feature is expected to have an icon (optional), a title, and a description.
 *
 * Expected variables from the including page:
 * @var array  $features_list    An array of features to display. Each feature should be an associative array
 *                                with keys like 'icon' (optional, Material Icon name), 'title', and 'description'.
 *                                If empty or not an array, the component will not render.
 * @var string $grid_cols_class  The Tailwind CSS class for defining the number of columns in the grid
 *                                for medium-sized screens and above. Defaults to 'md:grid-cols-3'.
 */

// includes/ui_components/features_section.php
if (empty($features_list) || !is_array($features_list)) {
    return;
}
$grid_cols_class = $grid_cols_class ?? 'md:grid-cols-3';
?>
<div class="grid <?php echo htmlspecialchars($grid_cols_class); ?> gap-8 text-center">
    <?php foreach ($features_list as $feature): ?>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <?php if (isset($feature['icon'])): ?>
        <span class="material-icons text-5xl text-blue-500 mb-4"><?php echo htmlspecialchars($feature['icon']); ?></span>
        <?php endif; ?>
        <h3 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($feature['title']); ?></h3>
        <p class="text-gray-600"><?php echo htmlspecialchars($feature['description']); ?></p>
    </div>
    <?php endforeach; ?>
</div>
