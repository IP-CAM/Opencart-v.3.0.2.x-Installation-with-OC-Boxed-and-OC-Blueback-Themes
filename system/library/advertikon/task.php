<?php
/**
 * Advertikon Task Class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 *
 * To install it run self::install() (as a rule you need to do it during the main extension installation)
 * To uninstall run self::uninstall() (as a rule you need to do it during the mail extension installation)
 * Extension need to have Catalog::controller::amend_task action
 * 
 * To run tasks call self::run(). It should be called via cronjob of some sort
 * In order to correctly end task each task action should have self::stop_task( ID). ID is passed via GET['id']
 */

namespace Advertikon;

class Task {

	public $task = '';
	public $schedule = '';
	public $status = '';
	public $last_run = '';
	public $p_id = '';
	public $threshold = '';
	public $h = '';
	private $tasks = '';
	public $id = '';
	public $table = 'adk_task';
	// protected $connector = null;
	protected $mutex_dir = '';

	public function __construct() {
		// $this->connector = array( $this, 'socket_connector' );
		$this->mutex_dir = ADK()->data_dir . 'task/';

		if ( !is_dir( $this->mutex_dir ) ) {
			@mkdir( $this->mutex_dir, 0777, true );
		}
	}

	/**
	 * Initializes object
	 * @return void
	 */
	public function init() {
		if ( !$this->tasks ) {
			$this->tasks = ADK()->q( array(
				'table' => $this->table,
				'query' => 'select',
			) );
		}
	}

	/**
	 * Installs task manager into system
	 * @return object
	 */
	public function install() {
		ADK()->db->query( "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->table . "`
		(`id`        INT         UNSIGNED AUTO_INCREMENT KEY,
		 `task`      TEXT,
		 `schedule`  VARCHAR(20),
		 `status`    TINYINT     UNSIGNED DEFAULT 0,
		 `last_run`  DATETIME,
		 `p_id`      VARCHAR(50),
		 `threshold` INT         UNSIGNED
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin" );

		ADK()->log( 'Task table has been created' );

		return $this;
	}

	/**
	 * Removes task manager from the system
	 * @param string $code Module code
	 * @return object
	 */
	public function uninstall( $code ) {
		ADK()->log( 'Removing module task(s)...' );

		$q = ADK()->q( [
			'table' => $this->table,
			'query' => 'delete',
			'where' => [
				'field'     => 'p_id',
				'operation' => '=',
				'value'     => $code,
			],
		] );

		$q = ADK()->q()->log( 1 )->run_query( [
			'table' => $this->table,
			'field' => [ 'count' => 'COUNT(*)']
		] );

		if ( $q['count'] == 0 ) {
			ADK()->log( 'Task table is empty - remove it' );

			ADK()->q( [
				'table' => $this->table,
				'query' => 'drop',
			] );
		}

		return $this;
	}

	/**
	 * Adds cron task
	 * @param string $task Task action (OpenCart action absolute URL)
	 * @param string $schedule Schedule structure (something like * * * * *)
	 * @param int $threshold Staleness threshold in seconds
	 * @return object
	 */
	public function add_task( $task, $schedule, $code, $threshold = 0  ) {
		$exists = ADK()->q( [
			'table' => $this->table,
			'query' => 'select',
			'where' => [
				[
					'field'     => '`task`',
					'operation' => '=',
					'value'     => '"' . $task . '"',
				],
				[
					'field'     => '`schedule`',
					'operation' => '=',
					'value'     => $schedule,
				],
				[
					'field'     => '`threshold`',
					'operation' => '=',
					'value'     => $threshold,
				],
			],
		] );

		if ( !$exists ) return $this; // Error

		if ( !count( $exists ) ) { // Does not exist
			$result = ADK()->q( array(
				'table' => $this->table,
				'query' => 'insert',
				'values' => array(
					'task'      => '"' . $task . '"',
					'schedule'  => $schedule,
					'threshold' => $threshold,
					'p_id'      => $code, // now using as module identifier
				),
			) );

			if ( $result ) {
				ADK()->log( sprintf( "Adding task %s %s %s", $task, $schedule, $threshold ) );

			} else {
				ADK()->log( sprintf( "Failed to add task %s %s %s", $task, $schedule, $threshold ) );
			}

		} else {
			ADK()->log( sprintf( "Task %s %s %s already exists", $task, $schedule, $threshold ) );
		}

		return $this;
	}

	/**
	 * Deletes cron task
	 * @param string $task Task action (OpenCart action absolute URL)
	 * @param string $schedule Schedule structure (something like * * * * *)
	 * @param int $threshold Staleness threshold in seconds
	 * @return object
	 */
	public function delete_task( $task, $schedule, $threshold = 0 ) {
		$result = ADK()->q( array(
			'table' => $this->table,
			'query' => 'delete',
			'where' => array(
				array(
					'field'     => 'task',
					'operation' => '=',
					'value'     => $task,
				),
				array(
					'field'     => 'schedule',
					'operation' => '=',
					'value'     => $schedule
				),
				array(
					'field'     => 'threshold',
					'operation' => '=',
					'value'     => $threshold
				),
			),
		) );

		if ( $result ) {
			ADK()->log( sprintf( "Deleting task %s %s %s", $task, $schedule, $threshold ) );

		} else {
			ADK()->log( sprintf( "Failed to delete task %s %s %s", $task, $schedule, $threshold ) );
		}

		return $this;
	}

	/**
	 * Checks whether task is exists
	 * @param string $action Task's task action 
	 * @param string $schedule Task's schedule
	 * @param int $threshold Task's threshold 
	 * @return boolean
	 */
	public function task_exists( $action, $schedule, $threshold ) {
		$query = ADK()->q( array(
			'table'     => $this->table,
			'field'     => array( 'count' => 'count(*)' ),
			'where'     => array(
				array(
					'field'     => 'task',
					'operation' => '=',
					'value'     => $action,
				),
				array(
					'field'     => 'schedule',
					'operation' => '=',
					'value'     => $schedule,
				),
				array(
					'field'     => 'threshold',
					'operation' => '=',
					'value'     => $threshold,
				),
			),
		) );

		return (boolean)$query['count'];
	}

	/**
	 * Run tasks
	 * @return void
	 */
	public function run() {
		ADK()->debug( 'Starting task execution...' );

		$q = ADK()->db->query( 'show tables like "' . DB_PREFIX . $this->table . '"' );

		if ( !$q->num_rows) {
			ADK()->log( 'Tasks table does not exist. Stop execution' );
			return;
		}

		if ( !@touch( $this->mutex_dir . 'cron' ) ) {
			ADK()->log( sprintf( 'Failed to update task status (failed "touch" file %s)', $this->mutex_dir . 'cron' ) );
		}

		ADK()->log( 'Task status updated' );

		if( false === ( $fd = @fopen( $this->mutex_dir . 'task', 'w+' ) ) ) {
			ADK()->error( sprintf( 'Failed to open mutex for task runner' ) );

			return false;
		}

		if( !flock( $fd, LOCK_EX ) ) {
			ADK()->error( sprintf( 'Active task detected. You need to increase interval between the tasks run.' ) );

			return false;
		}

		ADK()->log( 'Task mutex acquired' );

		while( $this->fetch_new() ) {
			$this->run_task();
		}

		if ( !flock( $fd, LOCK_UN ) ) {
			ADK()->error( 'Failed to release task\'s mutex gracefully' );

		} else {
			ADK()->log( 'Task mutex released' );
		}

		return true;
	}

	/**
	 * Checks task status
	 * @return array
	 * cron - if CRON JOB is set correctly
	 * task - if task is running
	 * count - number of active tasks 
	 */
	public function get_status() {
		$ret = [
			'cron'      => 0,
			'task'      => 0,
			'count'     => 0,
			'installed' => 0,
		];

		$query = ADK()->db->query( "SHOW TABLES LIKE '" . DB_PREFIX . $this->table . "'" );

		if ( $query && $query->row ) {
			$ret['installed'] = 1;

			// Cron is active
			if ( is_file( $this->mutex_dir . 'cron' ) && fileatime( $this->mutex_dir . 'cron' ) + 60 * 60 * 2 > time() ) {
				$ret['cron'] = 1;
			}

			// Check if some task is running
			if ( is_file( $this->mutex_dir . 'task' ) ) {
				$fd = fopen( $this->mutex_dir . 'task', 'r+' );

				if ( $fd ) {
					if ( !flock( $fd, LOCK_EX ) ) {
						$ret['task'] = 1;

						flock( $fd, LOCK_UN );
					}
				}
			}

			$q = ADK()->q()->log( 0 )->run_query( [
				'table' => $this->table,
				'query' => 'select',
				'field' => [ 'count' => 'count(*)', ],
			] );

			if ( count( $q ) ) {
				$ret['count'] = $q['count'];
			}
		}

		return $ret;
	}

	public function get_status_text() {
		$status  = $this->get_status();
		$error_w = '<span style="color: red; font-weight: bold;">%s</span>';
		$ok_w    = '<span style="color: green; font-weight: bold;">%s</span>';

		if ( !$status['installed'] ) {
			$ret = sprintf( $error_w, ADK()->__( 'Task manager is not installed. Unistall/install the module to fix it' ) );

		} else if ( !$status['count'] ) {
			$ret = sprintf( $error_w, ADK()->__( 'No one task is registered. Uninstall/install the module to fix it' ) );

		} else if ( !$status['cron'] ) {
			$ret = sprintf( $error_w, ADK()->__( 'Task manager is not working. Add task endpoint as crontab job' ) );

		} else {
			$ret = sprintf( $ok_w, ADK()->__( 'OK' ) );
		}

		return $ret;
	}

	/**
	 * Marks task as running
	 * @return boolean Operation status
	 */
	public function run_task() {
		if ( !$this->id ) {
			ADK()->error( 'Task ID is missing. Task queue needs to be initialized beforehand' );

			return false;
		}

		ADK()->log( sprintf( 'Start task %s', $this->task ) );
		ADK()->console->profiler( 'task' );

		$output = '';
		$self = $this;

		ADK()->do_clean( function() use ( $self ) {

			// Class::Method notation
			if ( ( $pos = strpos( $self->task, '::' ) ) !== false ) {
				$class = substr( $self->task, 0 , $pos );
				$method = substr( $self->task, $pos + 2 );

				$class = new $class;
				$class->{$method}();

			} else {
				$url = new Url( ADK() );
				$query = $url->parse( $self->task )->get_query();

				if ( !isset( $query['route'] ) ) {
					ADK()->error( sprintf( 'Failed to fetch route for task %s', $self->task ) );

					return;
				}

				ADK()->load->controller( $query['route'] );
			}

		}, $output );

		ADK()->debug( sprintf( 'End task', $self->task ) );
		ADK()->console->profiler( 'task' );

		if ( $output && true ) {
			ADK()->error( 'Output from task script', $output );
		}
	}

	/**
	 * Fetches new task from queue
	 * @return boolean Operation result
	 */
	public function fetch_new() {
		$this->init();

		if ( $this->task ) {
			$this->reset();
			$this->tasks->next();
		}

		while ( $this->tasks->valid() && !$this->is_scheduled() ) {
			$this->tasks->next();
		}

		if ( $this->tasks->valid() ) {
			$task = $this->tasks->current();

			$this->task      = $task['task'];
			$this->schedule  = $task['schedule'];
			$this->status    = $task['status'];
			$this->last_run  = $task['last_run'];
			$this->p_id      = $task['p_id'];
			$this->threshold = $task['threshold'];
			$this->id        = $task['id'];

			return true;
		}

		return false;
	}

	/**
	 * Resets task
	 * @return void
	 */
	public function reset() {
		$this->task      = '';
		$this->schedule  = '';
		$this->status    = '';
		$this->last_run  = '';
		$this->p_id      = '';
		$this->threshold = '';
		$this->id        = '';
	}

	/**
	 * Checks whether task is scheduled to be run NOW
	 * @return boolean
	 */
	public function is_scheduled( $schedule = null ) {
		if ( is_null( $schedule ) ) {
			$task = $this->tasks->current();
			$schedule = $task['schedule'];
		}

		$date = new \DateTime();
		$parts = explode( ' ', $schedule );

		if ( ! isset( $parts[ 4 ] ) ) {
			ADK()->error( sprintf( 'Task schedule: invalid schedule format: "%s"', $schedule ) );
			return false;
		}

		return  $this->is_min( $parts[0], $date ) &&
				$this->is_hour( $parts[1], $date ) &&
				$this->is_month( $parts[3], $date ) &&
				( $this->is_day( $parts[2], $date ) ||
				$this->is_week_day( $parts[4], $date ) );
	}

	/**
	 * Checks minute part of task schedule
	 * @param string $min Minutes part of schedule
	 * @param object $date DateTime object
	 * @return boolean
	 */
	public function is_min( $min, $date ) {
		try {

			if ( false === ( $parts = $this->parse_part( $min ) ) ) {
				$this->h->exception( 'error' );
			}

			if ( '*' === $parts['from'] ) {
				return true;
			}

			$min = (int)$date->format( 'i' );

			if ( $parts['from'] < 0 || $parts['from'] > 59 ) {
				$this->h->exception( 'error' );
			}

			if ( $parts['to'] < 0 || $parts['to'] > 59 ) {
				$this->h->exception( 'error' );
			}

			if ( $min < $parts['from'] || $min > $parts['to'] || 0 !== $min % $parts['divider'] ) {
				return false;
			}

		} catch ( Exception $e ) {
			ADK()->error( 'Task schedule: invalid format of schedule\'s minutes part' );
			return false;
		}

		return true;
	}

	/**
	 * Checks hour's part of task schedule
	 * @param string $min Hour's part of schedule
	 * @param object $date DateTime object
	 * @return boolean
	 */
	public function is_hour( $hour, $date ) {
		try {

			if ( false === ( $parts = $this->parse_part( $hour ) ) ) {
				$this->h->exception( 'error' );
			}

			if ( '*' === $parts['from'] ) {
				return true;
			}

			$min = (int)$date->format( 'H' );

			if ( $parts['from'] < 0 || $parts['from'] > 23 ) {
				$this->h->exception( 'error' );
			}

			if ( $parts['to'] < 0 || $parts['to'] > 23 ) {
				$this->h->exception( 'error' );
			}

			if ( $hour < $parts['from'] || $hour > $parts['to'] || 0 !== $hour % $parts['divider'] ) {
				return false;
			}

		} catch ( Exception $e ) {
			ADK()->error( 'Task schedule: invalid format of schedule\'s hours part' );
			return false;
		}

		return true;
	}

	/**
	 * Checks day's part of task schedule
	 * @param string $min Day's part of schedule
	 * @param object $date DateTime object
	 * @return boolean
	 */
	public function is_day( $day, $date ) {
		try {

			if ( false === ( $parts = $this->parse_part( $day ) ) ) {
				$this->h->exception( 'error' );
			}

			if ( '*' === $parts['from'] ) {
				return true;
			}

			$day = (int)$date->format( 'd' );

			if ( $parts['from'] < 1 || $parts['from'] > 31 ) {
				$this->h->exception( 'error' );
			}

			if ( $parts['to'] < 1 || $parts['to'] > 31 ) {
				$this->h->exception( 'error' );
			}

			if ( $day < $parts['from'] || $day > $parts['to'] || 0 !== $day % $parts['divider'] ) {
				return false;
			}

		} catch ( Exception $e ) {
			ADK()->error( 'Task schedule: invalid format of schedule\'s day part' );
			return false;
		}

		return true;
	}

	/**
	 * Checks month's part of task schedule
	 * @param string $min Month's part of schedule
	 * @param object $date DateTime object
	 * @return boolean
	 */
	public function is_month( $month, $date ) {
		try {

			if ( false === ( $parts = $this->parse_part( $month ) ) ) {
				$this->h->exception( 'error' );
			}

			if ( '*' === $parts['from'] ) {
				return true;
			}

			$month = (int)$date->format( 'm' );

			if ( $parts['from'] < 1 || $parts['from'] > 12 ) {
				$this->h->exception( 'error' );
			}

			if ( $parts['to'] < 1 || $parts['to'] > 12 ) {
				$this->h->exception( 'error' );
			}

			if ( $month < $parts['from'] || $month > $parts['to'] || 0 !== $month % $parts['divider'] ) {
				return false;
			}

		} catch ( Exception $e ) {
			ADK()->error( 'Task schedule: invalid format of schedule\'s month part' );
			return false;
		}

		return true;
	}

	/**
	 * Checks minute part of task schedule
	 * @param string $min Minutes part of schedule
	 * @param object $date DateTime object
	 * @return boolean
	 */
	public function is_week_day( $day, $date ) {
		try {

			if ( false === ( $parts = $this->parse_part( $day ) ) ) {
				$this->h->exception( 'error' );
			}

			if ( '*' === $parts['from'] ) {
				return true;
			}

			// 1 though 7
			$day = (int)$date->format( 'N' );

			if ( $parts['from'] < 0 || $parts['from'] > 7 ) {
				$this->h->exception( 'error' );
			}

			if ( $parts['to'] < 0 || $parts['to'] > 7 ) {
				$this->h->exception( 'error' );
			}

			if ( 0 === $parts['from'] ) {
				$parts['from'] = 7;
			}

			if ( 0 === $parts['to'] ) {
				$parts['to'] = 7;
			}

			if ( $day < $parts['from'] || $day > $parts['to'] || 0 !== $day % $parts['divider'] ) {
				return false;
			}

		} catch ( Exception $e ) {
			ADK()->error( 'Task schedule: invalid format of schedule\'s week\'s day part' );
			return false;
		}

		return true;
	}

	/**
	 * Parses schedule's parts (0/2, * / 1, 2-6/2 )
	 * @param string $part Schedule's part
	 * @return array|false
	 */
	public function parse_part( $part ) {
		if ( ! preg_match( '/(\*|\d+)(?:\s*-\s*(\d+))?(?:\s*\/\s*(\d+))?/', $part, $m ) ) {
			return false;
		}

		return array(
			'from'    => '*' === $m[1] ? '*' : (int)$m[1],
			'to'      => isset( $m[2] ) ? (int)$m[2] : (int)$m[1],
			'divider' => isset( $m[3] ) ? (int)$m[3] : 1,
		);
	}
}
