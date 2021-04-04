<?php

use BGAWorkbench\Test\Notification;

class feException extends Exception
{

}

class BgaSystemException extends feException
{

}

class BgaUserException extends feException
{

}

class APP_GameClass extends APP_DbObject
{

}

class Gamestate
{
    /**
     * @var array
     */
    public $table_globals;

    public function __construct()
    {
        $this->table_globals = [];
    }

    public function setAllPlayersMultiactive()
    {
    }

    public function setPlayerNonMultiactive($player_id, $next_state)
    {
        return false;
    }

    public function nextState($action = '')
    {
    }

    public function changeActivePlayer($player_id)
    {
    }
}

/**
 * Class Table
 *
 * @see https://en.doc.boardgamearena.com/Main_game_logic:_yourgamename.game.php
 */
abstract class Table extends APP_GameClass
{
    /**
     * @var Gamestate
     */
    public $gamestate;

	/**
	 * ID of the current player.
	 *
	 * @var int
	 */
	private $currentPlayerId;

	/**
	 * ID of the active player.
	 *
	 * @var int
	 */
	private $activePlayerId;

	/**
	 * @var array
	 */
	private $notifications = [];

    /**
     * @var array|null
     */
    private static $stubbedGameInfos;

    /**
     * Table constructor.
     */
    public function __construct()
    {
        $this->gamestate = new Gamestate();
    }

    abstract protected function setupNewGame($players, $options = array());

    public function initGameStateLabels($labels)
    {
    }

    public function reattributeColorsBasedOnPreferences($players, $colors)
    {
    }

    public function reloadPlayersBasicInfos()
    {
    }

    protected function activeNextPlayer()
    {
    }

    public function checkAction($actionName, $bThrowException = true)
    {
        return true;
    }

    private function getStatTypeId($targetName)
    {
        include('stats.inc.php');
        foreach ($stats_type as $type => $stats) {
            foreach ($stats as $name => $stat) {
                if ($name === $targetName) {
                    return $stat['id'];
                }
            }
        }
        throw new Exception('State not found: ' . $targetName);
    }

    public function initStat($table_or_player, $name, $value, $player_id = null)
    {
        $typeId = $this->getStatTypeId($name);
        $sql = 'INSERT INTO stats (stats_type, stats_player_id, stats_value) VALUES ';

        switch ($table_or_player) {
            case 'table':
                $sql .= sprintf('(%d, NULL, %s)', $typeId, $value);
                break;
            case 'player':
                $players = self::loadPlayersBasicInfos();
                if ($player_id === null) {
                    $values = [];
                    foreach (array_keys($players) as $id) {
                        $values[] = "('" . $typeId . "','$id','" . $value . "')";
                    }
                    $sql .= implode(', ', $values);
                } else {
                    $values[] = "('" . $typeId . "','$player_id','" . $value . "')";
                }
                break;
            default:
                throw new InvalidArgumentException(sprintf('Wrong table_or_player type: %s', $table_or_player));
        }

        self::DbQuery($sql);
    }

    public function incStat($delta, $name, $player_id = null)
    {
        $typeId = $this->getStatTypeId($name);
        if ($player_id === null) {
            self::DbQuery("UPDATE stats SET stats_value = stats_value + {$delta} WHERE stats_type = {$typeId}");
        } else {
            self::DbQuery("UPDATE stats SET stats_value = stats_value + {$delta} WHERE stats_type = {$typeId} AND stats_player_id = {$player_id}");
        }
    }

    public function setStat($value, $name, $player_id = null)
    {
        $typeId = $this->getStatTypeId($name);
        if ($player_id === null) {
            self::DbQuery("UPDATE stats SET stats_value = {$value} WHERE stats_type = {$typeId}");
        } else {
            self::DbQuery("UPDATE stats SET stats_value = {$value} WHERE stats_type = {$typeId} AND stats_player_id = {$player_id}");
        }
    }

    public function getStat($name, $player_id = null)
    {
        $typeId = $this->getStatTypeId($name);
        if ($player_id === null) {
            return self::getUniqueValueFromDB("SELECT stats_value FROM stats WHERE stats_type = ${typeId}");
        }
        return self::getUniqueValueFromDB("SELECT stats_value FROM stats WHERE stats_type = ${typeId} AND stats_player_id = {$player_id}");
    }

    /**
     * @param int $player_id
     * @param int $specific_time
     */
    public function giveExtraTime($player_id, $specific_time = null) {}

    /**
     * Get the "active_player" name.
     *
     * Note: avoid using this method in "multiplayer" state because it does not mean anything.
     *
     * @return string
     */
    public function getActivePlayerName()
    {
        $players = self::loadPlayersBasicInfos();
        return $players[$this->getActivePlayerId()]['player_name'];
    }

    ////////////////////////////////////////////////////////////////////////
    // Testing methods
    /**
     * @return array[]
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    public function resetNotifications()
    {
        $this->notifications = [];
    }

    /**
     * @param string $notification_type
     * @param string $notification_log
     * @param array $notification_args
     */
    public function notifyAllPlayers($notification_type, $notification_log, $notification_args)
    {
        $this->notifyPlayer('all', $notification_type, $notification_log, $notification_args);
    }

    /**
     * @param int $player_id
     * @param string $notification_type
     * @param string $notification_log
     * @param array $notification_args
     */
    public function notifyPlayer($player_id, $notification_type, $notification_log, $notification_args)
    {
        if ($notification_log === null) {
            throw new \InvalidArgumentException('Use empty string for notification_log instead of null');
        }
        $this->notifications[] = [
            'playerId' => $player_id,
            'type' => $notification_type,
            'log' => $notification_log,
            'args' => $notification_args
        ];
    }

    /**
     * Get the "current_player".
     *
     * The current player is the one from which the action originated (sender of the request).
     *
     * The current player is not necessarily the active player. Generally, the active player is someone who is making
     * a decision based on the actions of a player.
     *
     * Important: This method should only be used when in "multiplayer" state, and especially never in setupNewGame
     * or zombieTurn methods.
     *
     * @return int
     */
    protected function getCurrentPlayerId()
    {
        if ($this->currentPlayerId === null) {
            throw new \RuntimeException('Not a player bounded instance');
        }
        return $this->currentPlayerId;
    }

    /**
     * Get the current_player name.
     *
     * This method should only be used when in "multiplayer" state, and especially never in setupNewGame or zombieTurn methods.
     *
     * @return string
     */
    protected function getCurrentPlayerName()
    {
        return '';
    }

    /**
     * Get the "current_player" color.
     *
     * This method should only be used when in "multiplayer" state, and especially never in setupNewGame or zombieTurn methods.
     *
     * @return string
     */
    protected function getCurrentPlayerColor()
    {
        return '';
    }

    /**
     * Check the "current_player" zombie status. If true, player is zombie (e.g., left or was kicked out of game).
     *
     * @return bool
     */
    protected function isCurrentPlayerZombie()
    {
        return false;
    }

    /**
     * Check the "current_player" spectator status.
     *
     * If true, the user accessing the game is a spectator (not part of the game). For this user, the interface should
     * display all public information, and no private information (like a friend sitting at the same table as players
     * and just spectating the game).
     *
     * @return bool
     */
    protected function isSpectator()
    {
        return false;
    }

    /**
     * @param int $currentPlayerId
     * @return self
     */
    public function stubCurrentPlayerId($currentPlayerId)
    {
        $this->currentPlayerId = $currentPlayerId;
        return $this;
    }

    /**
     * Get the "active_player", whatever what is the current state type.
     *
     * Note: it does not mean that this player is active right now. Depends on the current state.
     *       Current state could be something else like "game" or "multiplayer".
     * Note: avoid using this method while in "multiplayer" state.
     *
     * @return int
     */
    public function getActivePlayerId()
    {
        return $this->activePlayerId;
    }

    /**
     * Get the color for the "active_player".
     *
     * @return string
     */
    public function getActivePlayerColor()
    {
        $player_id = self::getActivePlayerId();
        $players   = self::loadPlayersBasicInfos();

        return $players[$player_id]['player_color'] ?? '';
    }

    /**
     * @param int $activePlayerId
     * @return self
     */
    public function stubActivePlayerId($activePlayerId)
    {
        $this->activePlayerId = $activePlayerId;
        return $this;
    }

    /**
     * @param array $gameInfos
     */
    public static function stubGameInfos(array $gameInfos)
    {
        self::$stubbedGameInfos = $gameInfos;
    }

    /**
     * @param string $name
     * @return array
     */
    public static function getGameInfosForGame($name)
    {
        return self::$stubbedGameInfos;
    }

    /**
     * Returns an associatve array with generic data about players (e.g., not game-specific data).
     *
     * array (
     *     'player_id' => array(
     *         'player_name' => '', // string: The name of the player,
     *         'player_color' => '', // string: The color code of the player,
     *         'player_no' => 0, // string: The position of the player at the start of the game in natural table order.
     *     )
     * )
     *
     * @return array
     */
    public function loadPlayersBasicInfos()
    {
        $players = self::getObjectListFromDB('SELECT * FROM player');
        $playerIds = array_map(
            function (array $player) {
                return (int) $player['player_id'];
            },
            $players
        );
        return array_combine($playerIds, $players);
    }
}
