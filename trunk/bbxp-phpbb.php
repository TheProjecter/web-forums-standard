<?php

/**
 * phpBB WFXP Extension
 *
 * This class includes functions necessary for phpBB to interface
 * with the WFXP class, allowing for exportation of phpBB data to
 * a standard XML file.
 */
class BBXP_phpBB extends BBXP
{

	/**
	 * Sets up table names for database.
	 */
	function initialize_db ($host, $user, $pass, $database, $prefix)
	{
		$this->db->db_connect_host (array ('host' => $host, 'user' => $user, 'password' => $pass));
		$this->db->select ($database, $this->db->dbh);
		$this->db->tables['users'] = $prefix . 'users';
		$this->db->tables['forums'] = $prefix . 'forums';
		$this->db->tables['topics'] = $prefix . 'topics';
		$this->db->tables['posts'] = $prefix . 'posts';
		$this->db->tables['groups'] = $prefix . 'groups';
		$this->db->tables['user_group'] = $prefix . 'user_group';
	}

	/**
	 * Alias for BPDB's get_results that eliminates a parameter.
	 */
	function fetch ($query)
	{
		return $this->db->get_results ($query, 'ARRAY_A');
	}
	
	/**
	 * Fetches users from the database.
	 */
	function fetch_users ()
	{
		return $this->fetch ('SELECT * FROM ' . $this->db->tables['users'] . ' WHERE 1');
	}

	/**
	 * Fetches forums from the database.
	 */
	function fetch_forums ()
	{
		return $this->fetch ('SELECT * FROM ' . $this->db->tables['forums'] . ' WHERE 1');
	}

	/**
	 * Fetches topics from the database.
	 */
	function fetch_topics ()
	{
		return $this->fetch ('SELECT * FROM ' . $this->db->tables['topics'] . ' WHERE 1');
	}

	/**
	 * Fetches posts from the database.
	 */
	function fetch_posts ($topic_id)
	{
		return $this->fetch ('SELECT * FROM ' . $this->db->tables['posts'] . ' WHERE topic_id="' . $topic_id . '"');
	}

	/**
	 * Fetches group names from the database.
	 */
	function fetch_group_names ()
	{
		$raw_groups = $this->fetch ('SELECT group_id, group_name FROM ' . $this->db->tables['groups'] . ' WHERE 1');
		if ($raw_groups)
		{
			foreach ($raw_groups as $group)
			{
				$group_names[] = array ($group['group_id'] => $group['group_name']);
			}
		}
		return $group_names;
	}

	/**
	 * Fetches group data for a certain user from the database.
	 */
	function fetch_user_groups ($user_id, $group_names)
	{
		$group_ids = $this->fetch ('SELECT group_id FROM ' . $this->db->tables['user_group'] . ' WHERE user_id="' . $user_id . '"');
		if ($group_ids)
		{
			foreach ($group_ids as $group_id)
			{
				$groups[] = $group_names[$group_id];
			}
		}
		return $groups;
	}

	/**
	 * Prepares retrieved user data for output.
	 */
	function prep_user_data ($raw_user, $groups)
	{
		$user['id'] = $raw_user['user_id'];
		$user['login'] = $raw_user['username'];
		$user['pass']['type'] = 'phpass';
		$user['pass']['pass'] = $raw_user['user_password'];
		$user['incept'] = $raw_user['user_regdate'];
		$user['status'] = 0;
		$user['meta'][] = array ('type' => $raw_user['user_type']);
		$user['meta'][] = array ('permissions_phpBB' => $raw_user['user_permissions']);
		$user['meta'][] = array ('ip_address' => $raw_user['user_ip']);
		$user['meta'][] = array ('nice_name' => $raw_user['username_clean']);
		$user['meta'][] = array ('email' => $raw_user['user_email']);
		$user['meta'][] = array ('email_hash' => $raw_user['user_email_hash']);
		$user['meta'][] = array ('birthday' => $raw_user['user_birthday']);
		$user['meta'][] = array ('options' => $raw_user['user_options']);
		$user['meta'][] = array ('avatar' => $raw_user['user_avatar']);
		$user['meta'][] = array ('signature' => $raw_user['user_sig']);
		$user['meta'][] = array ('location' => $raw_user['user_from']);
		$user['meta'][] = array ('icq' => $raw_user['user_icq']);
		$user['meta'][] = array ('aim' => $raw_user['user_aim']);
		$user['meta'][] = array ('yim' => $raw_user['user_yim']);
		$user['meta'][] = array ('msn' => $raw_user['user_msnm']);
		$user['meta'][] = array ('jabber' => $raw_user['user_jabber']);
		$user['meta'][] = array ('url' => $raw_user['user_website']);
		$user['meta'][] = array ('form_salt' => $raw_user['user_form_salt']);
		$groups = implode ('||', $groups);
		$user['meta'][] = array ('groups' => $groups);
		return $user;
	}

	/**
	 * Prepares retrieved forum data for output.
	 */
	function prep_forum_data ($raw_forum)
	{
		$forum['id'] = $raw_forum['forum_id'];
		$forum['in'] = $raw_forum['parent_id'];
		$forum['title'] = $raw_forum['forum_name'];
		$forum['content'] = $raw_forum['forum_desc'];
		$forum['meta'][] = array ('link' => $raw_forum['forum_link']);
		$forum['meta'][] = array ('password' => $raw_forum['forum_password']);
		$forum['meta'][] = array ('rules' => $raw_forum['forum_rules']);
		$forum['meta'][] = array ('type' => $raw_forum['forum_type']);
		$forum['meta'][] = array ('flags' => $raw_forum['forum_flags']);
		return $forum;
	}

	/**
	 * Prepares retrieved topic data for output.
	 */
	function prep_topic_data ($raw_topic, $raw_posts)
	{
		$topic['id'] = $raw_topic['topic_id'];
		$topic['author'] = $raw_topic['topic_poster'];
		$topic['in'] = $raw_topic['forum_id'];
		$topic['title'] = $raw_topic['topic_title'];
		$topic['incept'] = $raw_topic['topic_time'];
		$topic['status'] = $raw_topic['topic_status'];
		$topic['meta'][] = array ('type' => $raw_topic['topic_type']);
		foreach ($raw_posts as $raw_post)
		{
			$topic['posts'][] = $this->prep_post_data ($raw_post);
		}
		return $topic;
	}

	/**
	 * Prepares retrieved post data for output.
	 */
	function prep_post_data ($raw_post)
	{
		$post['id'] = $raw_post['post_id'];
		$post['author'] = $raw_post['poster_id'];
		$post['title'] = '';
		$post['content'] = $raw_post['post_text'];
		$post['incept'] = $raw_post['post_time'];
		$post['meta'][] = array ('ip_address' => 'poster_ip');
		return $post;
	}

	/**
	 * Fetches, prepares, and outputs user data using subroutines.
	 */
	function write_users ()
	{
		$users = $this->fetch_users ();
		$group_names = $this->fetch_group_names ();
		foreach ($users as $user)
		{
			$groups = $this->fetch_user_groups ($user['user_id'], $group_names);
			$user = $this->prep_user_data ($user, $groups);
			$this->add_user ($user);
		}
	}

	/**
	 * Fetches, prepares, and outputs forum data using subroutines.
	 */
	function write_forums ()
	{
		$forums = $this->fetch_forums ();
		foreach ($forums as $forum)
		{
			$forum = $this->prep_forum_data ($forum);
			$this->add_forum ($forum);
		}
	}

	/**
	 * Fetches, prepares, and outputs topic data using subroutines.
	 */
	function write_topics ()
	{
		$topics = $this->fetch_topics ();
		foreach ($topics as $topic)
		{
			$topic_posts = $this->fetch_posts ($topic['topic_id']);
			$topic = $this->prep_topic_data ($topic, $topic_posts);
			$this->add_topic ($topic);
		}
	}

}

?>
