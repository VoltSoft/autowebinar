<?php

require_once 'vendor/autoload.php';
require_once 'config.php';

use Cron\CronExpression;


$cron = CronExpression::factory($schedule);

/**
 * Запускаем бесконечный цикл, который будет проверять
 * должна ли в настоящий момент быть запущена трансляция видео
 */
while (true) {

	/**
	 * Текущее время
	 */
	$now = time();
	print(" > Текущее время: " . date('Y-m-d H:i:s', $now) . "\n");


	/**
	 * Проверяем, пришло ли время запускать трансляцию?
	 */
	if ($cron->isDue()) {

		print(" > Трансляция должна быть запущена прямо сейчас!" . "\n");
		$command = "ffmpeg -re -i " . $pathToVideo . " -c copy -f flv rtmp://localhost/webinar/mystream";
		print($command . "\n");
		exec($command);

	} else {

		/**
		 * Проверяем, возможно трансляция уже давно должна быть запущена?
		 */
		print(" > Проверяем, возможно трансляция уже давно должна быть запущена?" . "\n");
		$startTranslationAt = $cron->getPreviousRunDate()->format("U");


		/**
		 * Если текущее время находится между временем начала трансляции и ее окончанием
		 * необходимо запустить трансляцию с нужно секунды
		 */
		if ($startTranslationAt < $now && $now < ($startTranslationAt + $duaration)) {
			$startFrom = $now - $startTranslationAt;
			print(" > Да! Трансляция должна быть запущена с " . $startFrom . " секунды" . "\n");
			print(" > Запускаем трансляцию!" . "\n");
			$command = "ffmpeg -re -ss " . $startFrom . " -i " . $pathToVideo . " -c copy -f flv rtmp://localhost/webinar/mystream";
			print($command . "\n");
			exec($command);
		} else {
			/**
			 * Сейчас пауза между вебинарами. Ждем следующий
			 */
			print(" > Последний вебинар уже завершился. Ждем следующий." . "\n");
			$startTranslationAt = $cron->getNextRunDate()->format("U");
			print(" > Следующая трансляция состоится: "  . date('Y-m-d H:i:s', $startTranslationAt)  . " (через " . ($startTranslationAt - $now) . " сек.)\n");
		}
	}


	print(" > Сон 5 секунд" . "\n");
	sleep(5);
	print("" . "\n");
}