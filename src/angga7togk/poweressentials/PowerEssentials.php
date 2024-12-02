<?php

namespace angga7togk\poweressentials;

use angga7togk\poweressentials\commands\CoordinatesCommand;
use angga7togk\poweressentials\commands\FlyCommand;
use angga7togk\poweressentials\commands\gamemode\AdvantureCommand;
use angga7togk\poweressentials\commands\gamemode\CreativeCommand;
use angga7togk\poweressentials\commands\gamemode\SpectatorCommand;
use angga7togk\poweressentials\commands\gamemode\SurvivalCommand;
use angga7togk\poweressentials\commands\healfeed\FeedCommand;
use angga7togk\poweressentials\commands\healfeed\HealCommand;
use angga7togk\poweressentials\commands\home\DelHomeCommand;
use angga7togk\poweressentials\commands\home\HomeCommand;
use angga7togk\poweressentials\commands\home\SetHomeCommand;
use angga7togk\poweressentials\commands\lobby\LobbyCommand;
use angga7togk\poweressentials\commands\lobby\SetLobbyCommand;
use angga7togk\poweressentials\commands\NicknameCommand;
use angga7togk\poweressentials\commands\SudoCommand;
use angga7togk\poweressentials\commands\warp\AddWarpCommand;
use angga7togk\poweressentials\commands\warp\DelWarpCommand;
use angga7togk\poweressentials\commands\warp\WarpCommand;
use angga7togk\poweressentials\config\PEConfig;
use angga7togk\poweressentials\i18n\PELang;
use angga7togk\poweressentials\manager\DataManager;
use angga7togk\poweressentials\manager\UserManager;
use angga7togk\poweressentials\message\Message;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class PowerEssentials extends PluginBase
{
	private static PowerEssentials $instance;
	private DataManager $dataManager;

	/** @var UserManager[] */
	private array $userManagers = [];

	private PELang $lang;


	protected function onLoad(): void
	{
		self::$instance = $this;
	}

	public function onEnable(): void
	{
		PEConfig::init();
		$this->loadResources();
		$this->loadCommands();
		$this->loadListeners();
		$this->dataManager = new DataManager($this);
	}

	public function registerUserManager(Player $player): void
	{
		$this->userManagers[$player->getName()] = new UserManager($player);
	}

	public function unregisterUserManager(Player $player): void
	{
		unset($this->userManagers[$player->getName()]);
	}

	public function getUserManager(Player $player): UserManager
	{
		return $this->userManagers[$player->getName()] ?? $this->userManagers[$player->getName()] = new UserManager($player);
	}

	public function getDataManager(): DataManager
	{
		return $this->dataManager;
	}

	private function loadResources(): void
	{
		$oldLanguageDir = $this->getDataFolder() . "language";
		if (file_exists($oldLanguageDir)) {
			$this->unlinkRecursive($oldLanguageDir);
		}

		$resources = $this->getResources();
		foreach ($resources as $resource) {
			$fileName = $resource->getFileName();
			$extension = $this->getFileExtension($fileName);

			if ($extension !== PELang::LANGUAGE_EXTENSION) continue;

			$lang = new PELang($resource);
			$this->getLogger()->debug("Loaded language file: {$lang->getLang()}.ini");
		}
		PELang::setConsoleLocale(PEConfig::getLang());
		$this->lang = PELang::fromConsole();
		$message = $this->lang->translateString("language.selected", [
			$this->lang->getName(),
			$this->lang->getLang(),
		]);
		$this->getLogger()->info($message);
	}

	private function unlinkRecursive(string $dir): bool
	{
		$files = array_diff(scandir($dir), [".", ".."]);
		foreach ($files as $file) {
			$path = $dir . DIRECTORY_SEPARATOR . $file;
			is_dir($path) ? $this->unlinkRecursive($path) : unlink($path);
		}
		return rmdir($dir);
	}

	private function getFileExtension(string $path): string
	{
		$exploded = explode(".", $path);
		return $exploded[array_key_last($exploded)];
	}

	private function loadListeners(): void
	{
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}

	private function loadCommands(): void
	{
		$commands = [
			'lobby' => [new LobbyCommand(), new SetLobbyCommand()],
			'fly' => [new FlyCommand()],
			'gamemode' => [new AdvantureCommand(), new CreativeCommand(), new SpectatorCommand(), new SurvivalCommand()],
			'nickname' => [new NicknameCommand()],
			'home' => [new HomeCommand(), new DelHomeCommand, new SetHomeCommand()],
			'coordinates' => [new CoordinatesCommand()],
			'warp' => [new WarpCommand(), new AddWarpCommand(), new DelWarpCommand()],
			'heal' => [new HealCommand()],
			'feed' => [new FeedCommand()],
			'sudo' => [new SudoCommand()],
		];

		foreach ($commands as $keyCmd => $valueCmd) {
			if (!PEConfig::isCommandDisabled($keyCmd)) {
				$this->getServer()->getCommandMap()->registerAll($this->getName(), $valueCmd);
			}
		}
	}

	public static function getInstance(): self
	{
		return self::$instance;
	}
}
