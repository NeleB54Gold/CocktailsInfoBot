<?php

# Ignore inline messages (via @)
if ($v->via_bot) die;

# Start the Cocktails class
$cti = new CocktailData($db);

# Private chat with Bot
if ($v->chat_type == 'private') {
	if ($bot->configs['database']['status'] && $user['status'] !== 'started') $db->setStatus($v->user_id, 'started');
	
	# Test API
	elseif ($v->command == 'test' and $v->isAdmin()) {
		$t = $bot->code(substr(json_encode($cti->getCocktail(0), JSON_PRETTY_PRINT), 0, 4096));
		$t .= PHP_EOL . $bot->code(substr(json_encode($cti->getIngredient(0), JSON_PRETTY_PRINT), 0, 4096));
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Start message
	elseif ($v->command == 'start' || $v->query_data == 'start') {
		$t = $bot->text_link('&#8203;', 'https://telegra.ph/file/0e18f6df7b6c16a1bdc1b.jpg') . $tr->getTranslation('startMessage');
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('switchInlineMode'), '', 'switch_inline_query');
		$buttons[] = [
			$bot->createInlineButton($tr->getTranslation('helpButton'), 'help'),
			$bot->createInlineButton($tr->getTranslation('aboutButton'), 'about'),
		];
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('changeLanguage'), 'changeLanguage');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons, 'def', 0);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons, 'def', 0);
		}
	}
	# Help command
	elseif ($v->command == 'help' || $v->query_data == 'help') {
		$buttons[] = [$bot->createInlineButton($tr->getTranslation('switchInlineMode'), '', 'switch_inline_query')];
		$buttons[] = [$bot->createInlineButton('◀️', 'start')];
		$t = $tr->getTranslation('helpMessage');
		if ($v->query_data) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id, $cbtext, $show);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# About command
	elseif ($v->command == 'about' || $v->query_data == 'about') {
		$buttons[] = [$bot->createInlineButton('◀️', 'start')];
		$t = $tr->getTranslation('aboutMessage', [explode(' ', phpversion())[0]]);
		if ($v->query_data) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id, $cbtext, $show);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
	# Change language
	elseif (strpos($v->query_data, 'changeLanguage') === 0) {
		$langnames = [
			'en' => '🇬🇧 English',
			'it' => '🇮🇹 Italiano',
			'fr' => '🇫🇷 Français'
		];
		if (strpos($v->query_data, 'changeLanguage-') === 0) {
			$select = str_replace('changeLanguage-', '', $v->query_data);
			if (in_array($select, array_keys($langnames))) {
				$tr->setLanguage($user['lang'] = $select);
				$db->query('UPDATE users SET lang = ? WHERE id = ?', [$user['lang'], $user['id']]);
			}
		}
		$langnames[$user['lang']] .= ' ✅';
		$formenu = 2;
		$mcount = 0;
		foreach ($langnames as $lang_code => $name) {
			if (isset($buttons[$mcount]) && count($buttons[$mcount]) >= $formenu) $mcount += 1;
			$buttons[$mcount][] = $bot->createInlineButton($name, 'changeLanguage-' . $lang_code);
		}
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		$bot->editText($v->chat_id, $v->message_id, 'Set your language:', $buttons);
		$bot->answerCBQ($v->query_id);
	}
	# Get Cocktail infos
	elseif (strpos($v->query_data, 'cocktail-') === 0) {
		$cocktail = $cti->getCocktail(str_replace('cocktail-', '', $v->query_data));
		if ($cocktail['name']) {
			foreach (json_decode($cocktail['ingredients'], true) as $ingredient) {
				if (isset($ingredient['amount'])) {
					$ingredients .= PHP_EOL . '● ' . $tr->getTranslation('amountOfIngredient', [round($ingredient['amount'], 1), $ingredient['unit'], $ingredient['ingredient']]);
					if ($ingredient['label']) $ingredients .= ' (' . $ingredient['label'] . ')';
				} elseif (isset($ingredient['special'])) {
					$ingredients .= PHP_EOL . '● ' . $ingredient['special'];
				}
			}
			$glass = explode('-', $cocktail['glass']);
			$cocktail['glass'] = $string = '';
			foreach ($glass as $string) {
				$string[0] = strtoupper($string[0]);
				$cocktail['glass'] .= $string;
			}
			if ($cocktail['glass'] == 'Martini') {
				$cocktail['glass'] = $tr->getTranslation('glass' . $cocktail['glass']) . ' 🍸';
			} elseif ($cocktail['glass'] == 'OldFashioned') {
				$cocktail['glass'] = $tr->getTranslation('glass' . $cocktail['glass']) . ' 🥃';
			} elseif ($cocktail['glass'] == 'ChampagneFlute') {
				$cocktail['glass'] = $tr->getTranslation('glass' . $cocktail['glass']) . ' 🥂';
			} elseif ($cocktail['glass'] == 'WhiteWine') {
				$cocktail['glass'] = $tr->getTranslation('glass' . $cocktail['glass']) . ' 🍷';
			} else {
				$cocktail['glass'] = $tr->getTranslation('glass' . $cocktail['glass']);
			}
			if (!$cocktail['category']) $cocktail['category'] = 'None';
			$category = explode('-', $cocktail['category']);
			$cocktail['category'] = $string = '';
			foreach ($category as $string) {
				$string[0] = strtoupper($string[0]);
				$cocktail['category'] .= $string;
			}
			$t = '🔎 ' . $bot->bold($cocktail['name']) . PHP_EOL . PHP_EOL .
			$bot->bold($tr->getTranslation('glass')) . ': ' . $cocktail['glass'] . PHP_EOL . 
			$bot->bold($tr->getTranslation('category')) . ': ' . $tr->getTranslation('category' . $cocktail['category']) . PHP_EOL . 
			$bot->bold($tr->getTranslation('ingredients')) . ': ' . $ingredients . PHP_EOL;
		} else {
			$t = $tr->getTranslation('cocktailNotFound');
		}
		$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
		if ($v->query_id) $bot->answerCBQ($v->query_id);
	}
	# Unknown command
	else {
		if ($v->text) {
			$cocktails = $cti->searchCocktail($v->text);
			if (!empty($cocktails) && !isset($cocktails['error'])) {
				$t = $bot->bold($tr->getTranslation('cocktailFound', [count($cocktails), $v->text]));
				if (count($cocktails) > 1) {
					foreach ($cocktails as $cocktail) {
						$buttons[][] = $bot->createInlineButton($cocktail['name'], 'cocktail-' . $cocktail['id']);
					}
				} else {
					$bot->editConfigs('response', true);
					$m = $bot->sendMessage($v->chat_id, $t);
					$bot->editConfigs('response', false);
					unset($v->text);
					$v->message_id = $m['result']['message_id'];
					$v->query_data = 'cocktail-' . $cocktails[0]['id'];
					require(__FILE__);
					die;
				}
			} else {
				$t = $tr->getTranslation('cocktailNotFound');
			}
		}
		elseif ($v->command) {
			$t = $tr->getTranslation('unknownCommand');
		} elseif ($v->query_data) {
			$t = 'Unknown button...';
		}
		if ($v->query_id) {
			$bot->answerCBQ($v->query_id, $t);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	}
}

# Inline commands
if ($v->update['inline_query']) {
	$results = [];
	if ($v->query) {
		$cocktails = $cti->searchCocktail($v->query);
		if (!empty($cocktails) and !isset($cocktails['error'])) {
			foreach ($cocktails as $cocktail) {
				foreach (json_decode($cocktail['ingredients'], true) as $ingredient) {
					if (isset($ingredient['amount'])) {
						$ingredients .= PHP_EOL . '● ' . $tr->getTranslation('amountOfIngredient', [round($ingredient['amount'], 1), $ingredient['unit'], $ingredient['ingredient']]);
						if ($ingredient['label']) $ingredients .= ' (' . $ingredient['label'] . ')';
					} elseif (isset($ingredient['special'])) {
						$ingredients .= PHP_EOL . '● ' . $ingredient['special'];
					}
				}
				$glass = explode('-', $cocktail['glass']);
				$cocktail['glass'] = $string = '';
				foreach ($glass as $string) {
					$string[0] = strtoupper($string[0]);
					$cocktail['glass'] .= $string;
				}
				if ($cocktail['glass'] == 'Martini') {
					$cocktail['glass'] = $tr->getTranslation('glass' . $cocktail['glass']) . ' 🍸';
				} elseif ($cocktail['glass'] == 'OldFashioned') {
					$cocktail['glass'] = $tr->getTranslation('glass' . $cocktail['glass']) . ' 🥃';
				} elseif ($cocktail['glass'] == 'ChampagneFlute') {
					$cocktail['glass'] = $tr->getTranslation('glass' . $cocktail['glass']) . ' 🥂';
				} elseif ($cocktail['glass'] == 'WhiteWine') {
					$cocktail['glass'] = $tr->getTranslation('glass' . $cocktail['glass']) . ' 🍷';
				} else {
					$cocktail['glass'] = $tr->getTranslation('glass' . $cocktail['glass']);
				}
				if (!$cocktail['category']) $cocktail['category'] = 'None';
				$category = explode('-', $cocktail['category']);
				$cocktail['category'] = $string = '';
				foreach ($category as $string) {
					$string[0] = strtoupper($string[0]);
					$cocktail['category'] .= $string;
				}
				$t = '🔎 ' . $bot->bold($cocktail['name']) . PHP_EOL . PHP_EOL .
				$bot->bold($tr->getTranslation('glass')) . ': ' . $cocktail['glass'] . PHP_EOL . 
				$bot->bold($tr->getTranslation('category')) . ': ' . $tr->getTranslation('category' . $cocktail['category']) . PHP_EOL . 
				$bot->bold($tr->getTranslation('ingredients')) . ': ' . $ingredients . PHP_EOL;
				$results[] = $bot->createInlineArticle(
					$id += 1,
					$cocktail['name'],
					$ingredients,
					$bot->createTextInput($t)
				);
			}
		}
	}
	$bot->answerIQ($v->id, $results);
}

?>