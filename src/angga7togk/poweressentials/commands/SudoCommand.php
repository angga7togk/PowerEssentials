<?php

namespace angga7togk\poweressentials\commands;

use angga7togk\poweressentials\i18n\PELang;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

class SudoCommand extends PECommand {

    public function __construct() {
        parent::__construct("sudo", "Execute command or send message as a selected player", "/sudo <target> <cmd or msg>");
        $this->setPrefix("sudo.prefix");
        $this->setPermission("sudo");
    }

    public function run(CommandSender $sender, string $prefix, PELang $lang, array $args): void {
        if (empty($args)) {
            $sender->sendMessage($prefix . $this->getUsage());
            return;
        }
        if (!($target = Server::getInstance()->getPlayerExact($args[0])) instanceof Player) {
            $sender->sendMessage($prefix . $lang->translateString('error.player.null'));
            return;
        }
        $target->chat(array_shift($args));
    }

}