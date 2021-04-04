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

    /**
     * Works like checkAction, but it does not check if the current player is also the active player.
     *
     * This method is rarely used, but is done specifically in certain game states when you want to authorize
     * additional actions for players that are not active at the moment.
     *
     * Example: allowing players to change their mind about a card selection while other players are still thinking
     * about their move.
     *
     * @param $action
     */
    public function checkPossibleAction($action)
    {
    }

    /**
     * Make a specific list of players active during a multiactive gamestate.
     *
     * This method sends an update notification to all players whose state has changed.
     * @param array  $players    Array of player IDs.
     * @param string $next_state The next state to transition into.
     * @param false  $bExclusive Appends players to active list if false; replaces active players if true.
     *
     * @return bool True if state transition happened, false otherwise.
     */
    public function setPlayersMultiactive($players, $next_state, $bExclusive = false)
    {
    }

    /**
     * Makes a specified player inactive during a multiactive game state.
     *
     * Usually, you call this method during a multiactive game state after a player did their action. It is also possible
     * to call it directly from a multiplayer action handler.
     *
     * If this player was the last active player, the method triggers the "next_state" transition.
     *
     * @param $player_id
     * @param $next_state
     *
     * @return bool True if state transition happened, false otherwise.
     */
    public function setPlayerNonMultiactive($player_id, $next_state)
    {
        return false;
    }

    /**
     * Makes all playing players the active player and sends an update notification to each player.
     *
     * Triggers the onUpdateActionButtons callback.
     *
     * This method is typically used at the beginning of a game state which transitions to multipleactiveplayer, when
     * multiple players have to perform some action. Do not use this method if you are going to make more changes
     * in the active player list (e.g., if you want to take away multipleactiveplayer status immediately afterward,
     * you would use setPlayersMultiactive instead).
     */
    public function setAllPlayersMultiactive()
    {
    }

    /**
     * All playing players are made inactive. Transition to the next state.
     *
     * @param $next_state
     */
    public function setAllPlayersNonMultiactive($next_state)
    {
    }

    /**
     * Change the current state to a new state.
     *
     * Important: $transition is the name of the transition, not the name of the target game state.
     *
     * @see https://en.doc.boardgamearena.com/Your_game_state_machine:_states.inc.php.
     * @param string $transition Name of the transition.
     */
    public function nextState($transition = '')
    {
    }

    /**
     * Returns an associatve array of current game state attributes.
     *
     * @see https://en.doc.boardgamearena.com/Your_game_state_machine:_states.inc.php
     * @return array
     */
    public function state()
    {
    }

    /**
     * Call this method to make any player active.
     *
     * Note: this method should only be called while in "game" state, not "activeplayer" or "multipleactiveplayer".
     *
     * @param int|string $player_id The ID of the player to activate.
     */
    public function changeActivePlayer($player_id)
    {
    }

    /**
     * Retrieve the list of active players.
     *
     * This method can be called at any time, but will return different values depending on the game state.
     *
     * During "game", it will return a void array.
     * During "activeplayer", it will return an array with a single value (the active player ID).
     * During "multipleactiveplayer", it will return an array of all the active player IDs.
     *
     * It is best practice to only use this method during the multipleactiveplayer state.
     */
    public function getActivePlayerList()
    {
    }

    /**
     * Sends an update notification about multiplayer changes.
     *
     * All multiactive "set*" methods do this, however you must call this yourself if you want to change state manually
     * using database queries for complex calculations.
     *
     * Do not call this method if using a different set* method.
     *
     * @param $next_state_if_none
     */
    public function updateMultiactiveOrNextState($next_state_if_none)
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

    /**
     * Make the next player active in the natural player order.
     *
     * Note: this method should only be called in a "game" state, not "activeplayer" or "multipleactiveplayer".
     */
    protected function activeNextPlayer()
    {
    }

    /**
     * Make the previous player active (in natural player order).
     *
     * Note: this method should only be called in a "game" state, not "activeplayer" or "multipleactiveplayer".
     */
    protected function activePrevPlayer()
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
