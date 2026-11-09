<?php
session_start();
require_once('dbconfig.inc.php');
if (!isset($_SESSION['loginfalse'])) {
  $_SESSION['loginfalse'] = false;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="images/fav.png" sizes="16x16 32x32" type="image/png">
  <title><?php echo $titlename; ?></title>
  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css" integrity="sha256-46r060N2LrChLLb5zowXQ72/iKKNiw/lAmygmHExk/o=" crossorigin="anonymous" />
  <style>
    .toplink a {
      color: #dddddd !important;
    }

    .toplink a:hover {
      color: #ffffff !important;
    }

    body {
      /*background: url("images/bgb.png") repeat;*/
      background: #FC466B;
      /* fallback for old browsers */
      background: -webkit-linear-gradient(to right, #0f8ce0, #FC466B);
      /* Chrome 10-25, Safari 5.1-6 */
      background: linear-gradient(to right, #0f8ce0, #FC466B);
      /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */

    }
  </style>
</head>

<body>
  <nav class="navbar navbar-inverse navbar-fixed-top" style="background-color:<?php echo $hrdbg; ?>">
    <div class="container-fluid">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="#" id="hdr" style="color:<?php echo $hrdcolor; ?>;"><?php echo $school_name; ?></a>
      </div>
      <div class="collapse navbar-collapse" id="myNavbar">
        <ul class="nav navbar-nav">
          <li class="toplink"><a href="student_affairs/checkrowandclass/" target="_blank">
              <span class="glyphicon glyphicon-folder-open"></span>
              &nbsp;ข้อมูลการมาโรงเรียนและเข้าเรียน</a>
          </li>
          <li class="toplink"><a href="student_affairs/publicstudent/" target="_blank">
              <i class="fas fa-sign-in-alt"></i>
              &nbsp;ระบบนักเรียน</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container-fluid">
    <div class="container-login-box" style="max-width:350px;margin:auto;background:none;border-radius: 8px;border: 2px solid #222;padding: 20px;box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);">
      <div class="container-form" style="margin-top:50px;">
        <form class="form-signin" method="post" action="process_login.php" style="background-color:rgba(0,0,0,0);" onsubmit="storeuserdata()">
          <div class="input-group">
            <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
            <input type="text" class="form-control" name="username" id="username" placeholder="name.sur@spm35" required autofocus>
          </div>
          <br>
          <div class="input-group">
            <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
            <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
          </div>
          <br>
          <button class="btn btn-lg btn-block" style="background:#222;color:#eee;border:1px solid #FFEE58;" type="submit">
            ลงชื่อเข้าใช้
          </button>
          <br>
          <div style="text-align: right;color:white;">v 2.1</div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">การแจ้งเตือน</h4>
        </div>
        <div class="modal-body">
          <p style="color:#151515">Username หรือ Password ไม่ถูกต้อง</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">ปิด</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      if (typeof(Storage) !== "undefined") {
        var u = localStorage.getItem("lu");
        var p = localStorage.getItem("lp");
        if (u != null) {
          $('#username').val(u);
          if (p != null) {
            $('#password').val(p);
          }
        }
      } else {
        alert('หน่วยความจำภายในขัดข้อง');
      }

      //alert for login false
      var sessPopup = '<?php echo $_SESSION['loginfalse']; ?>';
      if (sessPopup) {
        setTimeout(function() {
          $('#myModal').modal('show');
        }, 100);
        <?php $_SESSION['loginfalse'] = false; ?>
      }
    });

    //function store userinfo
    function storeuserdata() {
      if (typeof(Storage) !== "undefined") {
        var u = $('#username').val();
        var p = $('#password').val();
        localStorage.setItem("lu", u);
        localStorage.setItem("lp", p);
      } else {
        localStorage.setItem("lu", null);
        localStorage.setItem("lp", null);
      }
    }
  </script>
</body>

</html>
<?php $conn->close(); ?>