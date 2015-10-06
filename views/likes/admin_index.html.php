<?php

use lithium\g11n\Message;

$t = function($message, array $options = []) {
	return Message::translate($message, $options + ['scope' => 'base_like', 'default' => $message]);
};


$this->set([
	'page' => [
		'type' => 'multiple',
		'object' => $t('likes')
	]
]);

?>
<article
	class="use-rich-index"
	data-endpoint="<?= $this->url([
		'action' => 'index',
		'page' => '__PAGE__',
		'orderField' => '__ORDER_FIELD__',
		'orderDirection' => '__ORDER_DIRECTION__',
		'filter' => '__FILTER__'
	]) ?>"
>

	<?php if ($data->count()): ?>
		<table>
			<thead>
				<tr>
					<td class="title"><?= $t('Title') ?>
					<td data-sort="count-real" class="number table-sort" title="<?= $t('R = real, F = fake') ?>">
						<?= $t('Count (R/F)') ?>
					<td class="actions">
						<?= $this->form->field('search', [
							'type' => 'search',
							'label' => false,
							'placeholder' => $t('Filter'),
							'class' => 'table-search',
							'value' => $this->_request->filter
						]) ?>
			</thead>
			<tbody>
				<?php foreach ($data as $item): ?>
				<tr>
					<td class="title">
						<?php if ($liked = $item->poly()): ?>
							<?= $liked->title ?: $liked->name ?>
						<?php else: ?>
							?
						<?php endif ?>
					<td class="emphasize number">
						<span><?= $item->count('real') ?></span>
						/
						<span class="minor"><?= $item->count('fake') ?></span>
					<td class="actions">
				<?php endforeach ?>
			</tbody>
		</table>
	<?php else: ?>
		<div class="none-available"><?= $t('No items available, yet.') ?></div>
	<?php endif ?>

	<?=$this->view()->render(['element' => 'paging'], compact('paginator'), ['library' => 'base_core']) ?>
</article>