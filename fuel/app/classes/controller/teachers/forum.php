<?php

class Controller_Teachers_Forum extends Controller_Teachers
{

	public function before(){
		parent::before();
	}

	public function action_index()
	{

		if(Input::get("del_id", null) != null){
			$del_forum = Model_Forum::find(Input::get("del_id", 0));
			if($del_forum->user_id == $this->user->id){
				$del_forum->deleted_at = time();
				$del_forum->save();
			}
		}

		$where = [["deleted_at", 0]];
		$data["forum"] = Model_Forum::find("all", [
			"where" => $where,
			"order_by" => [
				["id", "desc"],
			]
		]);

		$config=array(
			'pagination_url'=>"?",
			'uri_segment'=>"p",
			'num_links'=>9,
			'per_page'=>20,
			'total_items'=>count($data["forum"]),
		);

		$data["pager"] = Pagination::forge('mypagination', $config);
		$data["news"] = array_slice($data["forum"], $data["pager"]->offset, $data["pager"]->per_page);
		$data["user"] = $this->user;
		$view = View::forge("teachers/forum/index", $data);
		$this->template->content = $view;
	}

	public function action_edit($id = 0)
	{
		$data["forum"] = Model_Forum::find($id);

		if($data["forum"] == null){
			$data["forum"] = Model_Forum::forge();
		}
		// add
		if(Input::post("title", "") != "" and Security::check_token()){
			// save
			$data["forum"]->title = Input::post("title", "");
			$data["forum"]->body = Input::post("body", "");
			if($id == 0){
				$data["forum"]->user_id = $this->user->id;
			}
			$data["forum"]->save();
			Response::redirect("/teachers/forum/");
		}
		$view = View::forge("teachers/forum/edit", $data);
		$this->template->content = $view;
	}

	public function action_comment($id = 0)
	{
		$data["comment"] = Model_Comment::find($id);
		if($data["comment"] == null){
			Response::redirect("/teachers/forum/");
		}
		// add
		if(Input::post("body", "") != "" and Security::check_token()){

			// save
			$data["comment"]->body = Input::post("body", "");
			$data["comment"]->save();
			Response::redirect("/teachers/forum/detail/{$data["comment"]->forum_id}");
		}
		$view = View::forge("teachers/forum/comment", $data);
		$this->template->content = $view;
	}

	public function action_detail($id = 0)
	{
		$data["forum"] = Model_Forum::find($id);

		if($data["forum"] == null){
			Response::redirect("/teachers/forum/");
		}

		if(Input::get("del_id", null) != null){
			$del_comment = Model_Comment::find(Input::get("del_id", 0));
			if($del_comment->user_id == $this->user->id){
				$del_comment->deleted_at = time();
				$del_comment->save();
			}
		}

		// add
		if(Input::post("body", "") != "" and Security::check_token()){
			// save
			$comment = Model_Comment::forge();
			$comment->body = Input::post("body", "");
			$comment->forum_id = $id;
			$comment->user_id = $this->user->id;
			$comment->save();
		}

		$data["user"] = $this->user;

		$view = View::forge("teachers/forum/detail", $data);
		$this->template->content = $view;
	}
}
