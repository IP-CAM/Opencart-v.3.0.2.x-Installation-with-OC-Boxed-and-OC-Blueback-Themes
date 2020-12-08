<?php
/**
 * Advertikon Task Trait
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

trait Trait_Task {
	/**
	 * Cron action
	 * @return void
	 */
	public function cron() {
		$task = new \Advertikon\Task();
		$task->run();
	}

	public function install_task() {
		$task = new \Advertikon\Task();
		$task->install();

		return $task;
	}

	public function uninstall_task() {
		$task = new \Advertikon\Task();
		$task->uninstall( $this->a->code );
	}

	public function get_task_status() {
		$task = new \Advertikon\Task();

		return $task->get_status();
	}

	public function render_task_status() {
		$task = new \Advertikon\Task();
		$endpoint = $this->a->u()->catalog_url( true ) . 'index.php?route=' . $this->a->full_name . '/cron';

		$ret = $this->a->r()->render_form_group( array(
			'label'       => $this->a->__( 'Task Endpoint' ),
			'description' => $this->a->__( 'Add it as system crontab task, eg * * * * * wget -t 1 -O - %s  >/dev/null 2>&1', $endpoint ),
			'element'     => $this->a->r( array(
				'type'        => 'text',
				'value'       => $endpoint,
				'class'       => 'form-control clipboard',
				'custom_data' => [ 'readonly' => 'true', ],
			) ),
		) );

		$ret .= $this->a->r()->render_form_group( array(
			'label'       => $this->a->__( 'Task Status' ),
			'element'     => $task->get_status_text(),
		) );

		return $ret;
	}
}
