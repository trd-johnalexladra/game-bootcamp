<div id="contents-wrap">
	<div id="main">
		<h3>Signup</h3>
		<section class="content-wrap">
			<form action="submit" method="post" enctype="multipart/form-data">
				<input type="hidden" id="id" value="<? echo Input::get("id", ""); ?>" />
				<ul class="forms">
					<li><h4>School Name</h4>
						<div><?= $school_name ?></div>
					</li>
					<li><h4>Email address</h4>
						<div><? echo $email; ?></div>
					</li>
					<li><h4>Password</h4>
						<div>********</div>
					</li>
					<li><h4>Contact Number</h4>
						<div><? echo $contact_no; ?></div>
                    </li>
				</ul>
				<p class="button-area">
					<button name="submit" value="1" class="button">Submit <i class="fa fa-chevron-right"></i></button>
				</p>
				<? echo Form::hidden(Config::get('security.csrf_token_key'), Security::fetch_token()); ?>
			</form>
		</section>
	</div>
</div>
