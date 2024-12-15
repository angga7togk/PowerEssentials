<?php

namespace angga7togk\poweressentials\commands;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use angga7togk\poweressentials\i18n\PELang;
use angga7togk\poweressentials\PowerEssentials;

class TPACommand extends PECommand
{
    public function __construct()
    {
        parent::__construct('tpa', 'Request teleport to or from a player', '/tpa <to|here|accept|deny|cancel> <player>');
        $this->setPrefix('tpa.prefix');
        $this->setPermission('tpa');
    }

    public function run(CommandSender $sender, string $prefix, PELang $lang, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage($prefix . $lang->translateString('error.console'));
            return;
        }

        if (count($args) < 2 && !in_array(strtolower($args[0] ?? ''), ['accept', 'deny', 'cancel'])) {
            $sender->sendMessage($prefix . $this->getUsage());
            return;
        }

        $mgr = PowerEssentials::getInstance()->getDataManager();
        $action = strtolower($args[0]);
        $target = isset($args[1]) ? Server::getInstance()->getPlayerExact($args[1]) : null;
        if ($target === null) {
          $sender->sendMessage($prefix . $lang->translateString('error.player.null'));
          return;
      }
        switch ($action) {
            case 'to':
                $mgr->setRequestTeleportTo($sender, $target);
                $sender->sendMessage($prefix . $lang->translateString('tpa.requested.to', [$target->getName()]));
                $target->sendMessage($prefix . $lang->translateString('tpa.request.to', [$sender->getName()]));
                break;

            case 'here':
                $mgr->setRequestTeleportHere($sender, $target);
                $sender->sendMessage($prefix . $lang->translateString('tpa.requested.here', [$target->getName()]));
                $target->sendMessage($prefix . $lang->translateString('tpa.request.here', [$sender->getName()]));
                break;

            case 'accept':
                $reqType = $mgr->getRequestTeleportType($sender, $target);
                if ($reqType === false) {
                    $sender->sendMessage($prefix . $lang->translateString('error.null'));
                    return;
                }

                if ($reqType === "to") {
                  $target->teleport($sender->getPosition());
                } else {
                  $sender->teleport($target->getPosition());
                }
                $mgr->removeTeleportRequest($target, $sender);

                $target->sendMessage($prefix . $lang->translateString('tpa.accept.target', [$sender->getName()]));
                $sender->sendMessage($prefix . $lang->translateString('tpa.accept.self'));
                break;

            case 'deny':
                $reqType = $mgr->getRequestTeleportType($sender, $target);
                if ($reqType === false) {
                    $sender->sendMessage($prefix . $lang->translateString('error.null'));
                    return;
                }

                $mgr->removeTeleportRequest($target, $sender);
                $target->sendMessage($prefix . $lang->translateString('tpa.error.deny.target', [$sender->getName()]));
                $sender->sendMessage($prefix . $lang->translateString('tpa.error.deny.self'));
                break;

            case 'cancel':
                $mgr->removeTeleportRequest($sender, $target);
                $sender->sendMessage($prefix . $lang->translateString('tpa.error.cancel.self'));
                break;

            default:
                $sender->sendMessage($prefix . $this->getUsage());
        }
    }
}