<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * @var CView $this
 */

$widget = (new CWidget())
	->setTitle(_('GUI'))
	->setTitleSubmenu(getAdministrationGeneralSubmenu());

// Append languages to form list.
$lang_combobox = (new CComboBox('default_lang', $data['default_lang']))->setAttribute('autofocus', 'autofocus');

$all_locales_available = 1;

foreach (getLocales() as $localeid => $locale) {
	if (!$locale['display']) {
		continue;
	}

	/*
	 * Checking if this locale exists in the system. The only way of doing it is to try and set one
	 * trying to set only the LC_MONETARY locale to avoid changing LC_NUMERIC.
	 */
	$locale_available = ($localeid === ZBX_DEFAULT_LANG || setlocale(LC_MONETARY, zbx_locale_variants($localeid)));

	$lang_combobox->addItem($localeid, $locale['name'], null, $locale_available);

	$all_locales_available &= (int) $locale_available;
}

// Restoring original locale.
setlocale(LC_MONETARY, zbx_locale_variants($data['default_lang']));

$language_error = '';
if (!function_exists('bindtextdomain')) {
	$language_error = 'Translations are unavailable because the PHP gettext module is missing.';
	$lang_combobox->setEnabled(false);
}
elseif ($all_locales_available == 0) {
	$language_error = _('You are not able to choose some of the languages, because locales for them are not installed on the web server.');
}

$timezones = DateTimeZone::listIdentifiers();

$gui_tab = (new CFormList())
	->addRow(_('Default language'),
		($language_error !== '')
			? [$lang_combobox, (makeErrorIcon($language_error))->addStyle('margin-left: 5px;')]
			: $lang_combobox
	)
	->addRow(_('Default time zone'),
		new CComboBox('default_timezone', $data['default_timezone'], null,
			[ZBX_DEFAULT_TIMEZONE => _('System')] + array_combine($timezones, $timezones)
		)
	)
	->addRow(_('Default theme'),
		new CComboBox('default_theme', $data['default_theme'], null, APP::getThemes())
	)
	->addRow((new CLabel(_('Limit for search and filter results'), 'search_limit'))->setAsteriskMark(),
		(new CNumericBox('search_limit', $data['search_limit'], 6))
			->setAriaRequired()
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
	)
	->addRow((new CLabel(_('Max count of elements to show inside table cell'), 'max_in_table'))->setAsteriskMark(),
		(new CNumericBox('max_in_table', $data['max_in_table'], 5))
			->setAriaRequired()
			->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH)
	)
	->addRow(_('Show warning if Zabbix server is down'),
		(new CCheckBox('server_check_interval', SERVER_CHECK_INTERVAL))
			->setUncheckedValue('0')
			->setChecked($data['server_check_interval'] == SERVER_CHECK_INTERVAL)
	);

$gui_view = (new CTabView())
	->addTab('gui', _('GUI'), $gui_tab)
	->setFooter(makeFormFooter(new CSubmit('update', _('Update'))));

$form = (new CForm())
	->setAttribute('aria-labeledby', ZBX_STYLE_PAGE_TITLE)
	->setAction((new CUrl('zabbix.php'))
		->setArgument('action', 'gui.update')
		->getUrl()
	)
	->addItem($gui_view);

$widget
	->addItem($form)
	->show();
