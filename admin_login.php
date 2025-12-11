
<?php 
session_start();
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to bottom right, #dfb0caff, #eeadadff);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      position: relative;
    }

  .header {
  width: 100%;
  background-color: #f51892e5;
  display: flex;
  justify-content: flex-start;
  align-items: center;
  padding: 15px 30px;
  position: absolute;
  top: 0;
  left: 0;
  box-shadow: 0 40px 60px rgba(230, 15, 201, 0.49); /* updated shadow */
}
.header {
      width: 100%;
      background-color: #ffe8cc;
      display: flex;
      align-items: center;
      padding: 15px 30px;
      box-shadow: 0 30px 60px rgba(230, 15, 201, 0.49);
    }

    .header img {
      height: 80px;
      width: 90px;
      margin-right: 25px;
      border: 3px solid black;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

   .header-text {
  flex-grow: 1;
  font-size: 28px;
  font-weight: bold;
  color: #291911;
  max-width: 1000px;
  line-height: 1.3;
 
}

    
    .card {
        background-color: #ffffff;
        padding: 2.5rem 2rem;
        border-radius: 12px;
        width: 100%;
        max-width: 400px;
        margin-top: 20px;
        box-shadow: 0 20px 80px rgba(5, 40, 85, 0.94); /* updated shadow */
        animation: dropIn 1s ease forwards;
        opacity: 0;
        transform: translateY(-300px);
        z-index: 1;
}


    @keyframes dropIn {
      0% { transform: translateY(-300px); opacity: 0; }
      70% { transform: translateY(30px); opacity: 1; }
      100% { transform: translateY(0); opacity: 1; }
    }

    .title {
      text-align: center;
      font-size: 1.8em;
      color: #160e07ff;
      margin-bottom: 1.5rem;
      font-weight: 700;
      letter-spacing: 1px;
      text-shadow:red;
    }

    .field {
      display: flex;
      align-items: center;
      background-color: #fff5ea;
      border-radius: 6px;
      padding: 0.6rem 1rem;
      margin-bottom: 1.2rem;
      border: 1px solid #e7d2bb;
      transition: all 0.3s ease;
    }

    .field:hover {
      background-color: #fce9d4;
    }

    .input-icon {
      width: 1.2em;
      height: 1.2em;
      margin-right: 0.8rem;
      fill: #d99e6a;
    }

    .input-field {
      background: none;
      border: none;
      outline: none;
      width: 100%;
      font-size: 1rem;
      color: #333;
    }

    .input-field::placeholder {
      color: #aaa;
    }

    .input-field:focus::placeholder {
      opacity: 0;
    }

    .btn {
      width: 100%;
      padding: 0.8rem;
      font-size: 1rem;
      border: none;
      border-radius: 40px;
      font-weight: bold;
      text-transform: uppercase;
      background-color: #f0c093;
      color: #0c0202ff;
      cursor: pointer;
      transition: background 0.3s ease, color 0.3s ease;
              box-shadow: 0 10px 20px rgba(253, 11, 11, 0.94); /* updated shadow */

    }

    .btn:hover {
      background-color: #d99e6a;
      color: #fff;
    }

    @media (max-width: 500px) {
      .card {
        padding: 2rem 1.5rem;
      }

      .title {
        font-size: 1.5em;
      }

      .header {
        flex-direction: column;
        text-align: center;
        padding: 15px;
      }

      .header img {
        margin-bottom: 10px;
      }

      .header-text {
        font-size: 14px;
        
      }

      .announcement {
        font-size: 16px;
        padding: 10px;
      }
    }
  </style>
</head>
<body>

  <div class="header">
    <img src="images/contin_kle.png" alt="KLE Logo">
    <div class="header-text">
      <span>KLE Technological University, Dr. M S Sheshgiri Campus, Udyambag, Belagavi - 590 008</span>
    </div>
  </div>

  

  <div class="card">
    <div class="title">Admin Login</div>
    <form action="admin_do_login.php" method="POST">
      <div class="field">
        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M12 12c2.67 0 8 1.34 8 4v2H4v-2c0-2.66 5.33-4 8-4zm0-2a4 4 0 100-8 4 4 0 000 8z"/>
        </svg>
        <input type="text" name="username" class="input-field" placeholder="Username" required>
      </div>
      <div class="field">
        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path d="M12 17a2 2 0 100-4 2 2 0 000 4zm6-10h-1V6a5 5 0 00-10 0v1H6c-1.1 0-2 .9-2 2v10a2 2 0 002 2h12a2 2 0 002-2V9c0-1.1-.9-2-2-2zm-6 10a4 4 0 110-8 4 4 0 010 8zm-3-10V6a3 3 0 016 0v1h-6z"/>
        </svg>
        <input type="password" name="password" class="input-field" placeholder="Password" required>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
  </div>

</body>
</html>
