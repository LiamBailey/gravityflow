<?php

class Gravity_Flow_Installation_Wizard_Step_Pages extends Gravity_Flow_Installation_Wizard_Step {

	protected $_name = 'pages';

	public function display() {
		if ( $this->workflow_pages == '' ) {
			// first run
			$this->workflow_pages = 'admin';
		};
		echo '<p>' . esc_html__( "Gravity Flow can be accessed from both the front end of your site and from the built-in WordPress admin pages (Workflow menu). If you want to use your site styles, or if you want to use the one-click approval links, then you'll need to add some pages to your site.", 'gravityflow' ) . '</p>';
		echo '<p>' . sprintf( esc_html__( 'Would you like to create custom inbox, status, and submit pages now? The pages will contain the %s[gravityflow] shortcode%s enabling assignees to interact with the workflow from the front end of the site.', 'gravityflow' ), '<a href="http://docs.gravityflow.io/article/36-the-shortcode" target="_blank">', '</a>' ) . '</p>';

		?>

        <div>
            <label>
                <input type="radio" value="admin" <?php checked( 'admin', $this->workflow_pages ); ?> name="workflow_pages"/>
				<?php esc_html_e( 'No, use the WordPress Admin (Workflow menu).', 'gravityflow' ); ?>
            </label>
        </div>
        <div>
            <label>
                <input type="radio" value="custom" <?php checked( 'custom', $this->workflow_pages ); ?> name="workflow_pages"/>
				<?php esc_html_e( 'Yes, create inbox, status, and submit pages now.', 'gravityflow' ); ?>
            </label>
        </div>

		<?php

	}

	public function get_title() {
		return esc_html__( 'Workflow Pages', 'gravityflow' );
	}

	public function install() {
		if ( $this->workflow_pages == 'custom' ) {
			$settings                = gravity_flow()->get_app_settings();
			$settings['inbox_page']  = $this->create_page( 'inbox' );
			$settings['status_page'] = $this->create_page( 'status' );
			$settings['submit_page'] = $this->create_page( 'submit' );
			gravity_flow()->update_app_settings( $settings );
		}
	}

	public function create_page( $page ) {
		$post = array(
			'post_title'   => $this->get_page_title( $page ),
			'post_content' => sprintf( '[gravityflow page="%s"]', $page ),
			'post_excerpt' => $this->get_page_title( $page ),
			'post_status'  => 'publish',
			'post_type'    => 'page',
		);

		$post_id = wp_insert_post( $post );

		return $post_id ? $post_id : '';
	}

	public function get_page_title( $page ) {
		$titles = array(
			'inbox'  => esc_html__( 'Workflow Inbox', 'gravityflow' ),
			'status' => esc_html__( 'Workflow Status', 'gravityflow' ),
			'submit' => esc_html__( 'Submit a Workflow Form', 'gravityflow' ),
		);

		return $titles[ $page ];
	}
}
