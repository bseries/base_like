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
					<td data-sort="User.number" class="user table-sort"><?= $t('User') ?>
					<td><?= $t('Type') ?>
					<td class="media">
					<td class="title"><?= $t('Title') ?>
					<td data-sort="count-real" class="table-sort" title="<?= $t('R = real, F = fake') ?>">
						<?= $t('Count (R/F)') ?>
					<td data-sort="created" class="date created table-sort desc"><?= $t('Created') ?>
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
					<?php $liked = $item->poly() ?>
				<tr>
					<td class="user">
						<?= $this->user->link($item->user()) ?>
					<td><?= $item->polyType() ?>
					<td class="media">
						<?php if ($liked && $liked->respondsTo('cover') && ($cover = $liked->cover())): ?>
							<?= $this->media->image($cover->version('fix3admin'), [
								'data-media-id' => $cover->id, 'alt' => 'preview'
							]) ?>
						<?php endif ?>
					<td class="title">
						<?php if ($liked): ?>
							<?= $liked->title() ?>
						<?php else: ?>
							?
						<?php endif ?>
					<td class="emphasize">
						<span><?= $item->count('real') ?></span>
						/
						<span class="minor"><?= $item->count('fake') ?></span>
					<td class="date created">
						<time datetime="<?= $this->date->format($item->created, 'w3c') ?>">
							<?= $this->date->format($item->created, 'date') ?>
						</time>
					<td class="actions">
						<?php if ($url = $item->polyUrl($this->_request, ['action' => 'edit'])): ?>
							<?= $this->html->link($t('open'), $url, ['class' => 'button']) ?>
						<?php endif ?>
				<?php endforeach ?>
			</tbody>
		</table>
	<?php else: ?>
		<div class="none-available"><?= $t('No items available, yet.') ?></div>
	<?php endif ?>

	<?=$this->view()->render(['element' => 'paging'], compact('paginator'), ['library' => 'base_core']) ?>
</article>