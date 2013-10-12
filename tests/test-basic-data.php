<?php

class DataBasicTest extends WP_UnitTestCase {

	function testAddition() {

		global $settings_revisions_plugin;
		$plugin = $settings_revisions_plugin;

		$admin_user = array_shift( get_users( array( 'role' => 'administrator' ) ) );
		wp_set_current_user( $admin_user->ID );

		$post = $plugin->post_type->get_active_post();
		$this->assertEmpty( $post );

		$post = $plugin->post_type->get_revision_settings( null );
		$this->assertNull( $post );

		$comment  = 'First revision setting blogname';
		$blogname = 'Hello World';
		$theme    = get_template();
		$revision = array(
			'comment' => $comment,
			'settings' => array(
				array(
					'id' => 'blogname',
					'type' => 'option',
					'value' => $blogname,
				),
			),
		);
		$post_id = $plugin->post_type->save_revision_settings( $revision );

		$this->assertTrue( is_int( $post_id ) );

		$post = get_post( $post_id );
		$this->assertTrue( $post instanceof WP_Post );
		$this->assertEquals( $post->post_title, $comment );

		$settings = $plugin->post_type->get_revision_settings( $post );

		$this->assertTrue( is_array( $settings ) );

		$this->assertEquals( count( $settings ), 1 );

		$setting = array_shift( $settings );
		$this->assertTrue( is_array( $setting ) );

		$this->assertEquals( count( $setting ), 3 );

		$this->assertArrayHasKey( 'id', $setting );
		$this->assertArrayHasKey( 'type', $setting );
		$this->assertArrayHasKey( 'value', $setting );

		$this->assertEquals( $setting['id'], 'blogname' );
		$this->assertEquals( $setting['type'], 'option' );
		$this->assertEquals( $setting['value'], $blogname );

		$this->assertEquals( $admin_user->ID, $post->post_author );
	}
}
