<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MahaRaj Hotel - Confirm Booking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <?php require('inc/links.php'); ?>
    <style>
        .pop:hover {
            border-top-color: var(--teal) !important;
            transform: scale(1.03);
            transition: all 0.3s;
        }
        #main {
            display: flex;
            flex-wrap: wrap;
            width: 100%;
            gap: 30px;
        }
        .rating i {
            font-size: 1.2rem;
        }
        .badge {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
    </style>
</head>
<body class="bg-light">
    <?php require('inc/header.php'); ?>

    <?php
    if (!isset($_GET['id']) || $settings_r['shutdown'] == true) {
        redirect('rooms.php');
    } elseif (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
        redirect('rooms.php');
    }

    $data = filteration($_GET);
    $room_res = select("SELECT * FROM `rooms` WHERE `id`=? AND `status`=? AND `removed`=?", 
                        [$data['id'], 1, 0], 'iii');

    if (mysqli_num_rows($room_res) == 0) {
        redirect('rooms.php');
    }

    $room_data = mysqli_fetch_assoc($room_res);

    $_SESSION['room'] = [
        "id" => $room_data['id'],
        "name" => $room_data['name'],
        "price" => $room_data['price'],
        "payment" => null,
        "available" => false,
    ];

    $user_res = select("SELECT * FROM `user_cred` WHERE `id`=? LIMIT 1", [$_SESSION['uId']], "i");
    $user_data = mysqli_fetch_assoc($user_res);
    ?>

    <div class="container">
        <div class="row g-4">
            <div class="col-12 my-5 mb-4 px-4">
                <h2 class="fw-bold h-font text-center">Confirm Booking</h2>
                <div style="font-size: 14px;">
                    <a href="index.php" class="text-secondary text-decoration-none">Home</a> >
                    <a href="rooms.php" class="text-secondary text-decoration-none">Rooms</a> >
                    <a href="#" class="text-secondary text-decoration-none">Confirm</a>
                </div>
            </div>

            <!-- Room Image Section -->
            <div class="col-lg-7 col-md-12 px-4">
                <?php
                $room_thumb = ROOMS_IMG_PATH . "thumbnail.jpg";
                $thumb_q = mysqli_query($con, "SELECT * FROM `room_images` WHERE `room_id`='$room_data[id]' AND `thumb`='1'");
                if (mysqli_num_rows($thumb_q) > 0) {
                    $thumb_res = mysqli_fetch_assoc($thumb_q);
                    $room_thumb = ROOMS_IMG_PATH . $thumb_res['image'];
                }
                ?>
                <div class="card p-3 shadow-sm rounded">
                    <img src="<?= $room_thumb ?>" class="img-fluid rounded mb-3">
                    <h5><?= $room_data['name'] ?></h5>
                    <h6>₹<?= $room_data['price'] ?> per night</h6>
                </div>
            </div>

            <!-- Booking Details Section -->
            <div class="col-lg-5 col-md-12 px-4">
                <div class="card shadow-sm mb-4 border-0 rounded-3">
                    <div class="card-body">
                        <h6 class="mb-3">Booking Details</h6>
                        <form action="pay_now.php" method="POST" id="booking_form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Name</label>
                                    <input name="name" value="<?= $user_data['name'] ?>" type="text" class="form-control shadow-none" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input name="phonenum" value="<?= $user_data['phonenum'] ?>" type="number" class="form-control shadow-none" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control shadow-none" required><?= $user_data['address'] ?></textarea>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label class="form-label ">Check-in</label>
                                    <input name="checkin"  onchange="check_availability()" type="date" class="form-control shadow-none" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Check-out</label>
                                    <input name="checkout" onchange="check_availability()" type="date" class="form-control shadow-none" required>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="spinner-border text-info mb-3 d-none"  id="info_loader" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <h6 class="mb-3 text-danger" id="pay_info">Provide check-in & check-out date!</h6>
                            <button name="pay_now" class="btn  btn-success w-100 custom-bg shadow-none mb-1" disabled>Pay Now</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php require('inc/footer.php'); ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let booking_form = document.getElementById('booking_form');
let info_loader = document.getElementById('info_loader');
let pay_info = document.getElementById('pay_info');
let pay_button = booking_form.elements['pay_now'];

function check_availability() {
    let checkin_val = booking_form.elements['checkin'].value;
    let checkout_val = booking_form.elements['checkout'].value;
    pay_button.setAttribute('disabled', true);

    if (checkin_val !== '' && checkout_val !== '') {
        pay_info.classList.add("d-none");
        pay_info.classList.replace("text-dark", "text-danger");
        info_loader.classList.remove("d-none");

        let data = new FormData();
        data.append('check_availability', '');
        data.append('check_in', checkin_val);
        data.append('check_out', checkout_val);

        let xhr = new XMLHttpRequest();
        xhr.open("POST", "ajax/confirm_booking_crud.php", true);

        xhr.onload = function () {
            let response = JSON.parse(this.responseText);

            if (response.status === "check_in_out_equal") {
                pay_info.innerText = "You cannot check out on the same day!";
            } else if (response.status === "check_out_earlier") {
                pay_info.innerText = "Check-out date is earlier than Check-in date!";
            } else if (response.status === "check_in_earlier") {
                pay_info.innerText = "Check-in date is earlier than today's date!";
            } else if (response.status === "unavailable") {
                pay_info.innerText = "Room not available for this check-in date!";
            } else {
                pay_info.innerHTML = "No. of Days: " + response.days + "<br>Total Amount to Pay: ₹" + response.payment;
                pay_info.classList.replace('text-danger', 'text-dark');
                pay_button.removeAttribute('disabled');
            }

            pay_info.classList.remove('d-none');
            info_loader.classList.add('d-none');
        };
        xhr.send(data);
    }
}


</script>

</body>
</html>
