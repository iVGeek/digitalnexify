const express = require('express');
const path = require('path');
const PesaPal = require('pesapal'); // Import the PesaPal library
const bcrypt = require('bcrypt');
const validator = require('validator');
const axios = require('axios');

const app = express();
const PORT = process.env.PORT || 3000;

const PESAPAL_BASE_URL = process.env.PESAPAL_BASE_URL || "https://cybqa.pesapal.com/pesapalv3";
const CONSUMER_KEY = process.env.PESAPAL_CONSUMER_KEY;
const CONSUMER_SECRET = process.env.PESAPAL_CONSUMER_SECRET;

// Middleware to parse JSON and URL-encoded data
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files from the "public" directory
app.use(express.static(path.join(__dirname, 'public')));

// PesaPal configuration
const pesapal = new PesaPal({
  consumerKey: 'dJx8ofTbwuSs3rPH0m8s7c142c1mVZht', // Replace with your PesaPal consumer key
  consumerSecret: 'PVjWH6PhjIVrz0+Zhcqtxnnp9NU=', // Replace with your PesaPal consumer secret
});

// Hardcoded admin credentials
const adminUsername = 'super';
const adminPasswordHash = bcrypt.hashSync('xsuper1', 10); // Hash the password

// Admin login route
app.post('/admin/login', (req, res) => {
  const { username, password } = req.body;

  if (username === adminUsername && bcrypt.compareSync(password, adminPasswordHash)) {
    return res.status(200).json({ message: 'Login successful' });
  }

  return res.status(401).json({ message: 'Invalid credentials' });
});

// User registration route
app.post('/register', async (req, res) => {
  const { username, email, password } = req.body;
  if (!username || !email || !password) {
    return res.status(400).send('All fields are required');
  }
  if (!validator.isEmail(email)) {
    return res.status(400).send('Invalid email format');
  }
  const hashedPassword = await bcrypt.hash(password, 10);
  // Save user logic here (e.g., database interaction)
  res.status(201).send('User registered successfully');
});

// User login route
app.post('/login', async (req, res) => {
  const { usernameOrEmail, password } = req.body;
  if (!usernameOrEmail || !password) {
    return res.status(400).send('All fields are required');
  }
  const user = await User.findOne({
    $or: [{ email: usernameOrEmail }, { username: usernameOrEmail }]
  });
  if (!user) {
    return res.status(404).send('User not found');
  }
  const isPasswordValid = await bcrypt.compare(password, user.password);
  if (!isPasswordValid) {
    return res.status(401).send('Invalid credentials');
  }
  // Generate token or session logic here
  res.status(200).send('Login successful');
});

// Route to initialize payment
app.post('/api/initiate-payment', async (req, res) => {
  const { firstName, lastName, email, phone, amount, description } = req.body;

  try {
    const paymentDetails = {
      amount,
      description,
      type: 'MERCHANT',
      reference: `REF-${Date.now()}`,
      firstName,
      lastName,
      email,
      phoneNumber: phone,
      currency: 'USD',
    };

    const paymentUrl = await pesapal.initiatePayment(paymentDetails);
    res.json({ success: true, paymentUrl });
  } catch (error) {
    console.error('Error initiating payment:', error);
    res.status(500).json({ success: false, message: 'Failed to initiate payment' });
  }
});

// Route to handle payment callback
app.get('/api/payment-callback', async (req, res) => {
  const { transactionId, reference } = req.query;

  try {
    const paymentStatus = await pesapal.getPaymentStatus(transactionId, reference);
    res.json({ success: true, paymentStatus });
  } catch (error) {
    console.error('Error fetching payment status:', error);
    res.status(500).json({ success: false, message: 'Failed to fetch payment status' });
  }
});

// Route to register an IPN URL
app.post('/api/register-ipn', async (req, res) => {
  const { url, ipn_notification_type } = req.body;

  try {
    const token = await getPesapalAccessToken();
    const response = await axios.post(
      `${PESAPAL_BASE_URL}/api/URLSetup/RegisterIPN`,
      { url, ipn_notification_type },
      {
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );
    res.json({ success: true, data: response.data });
  } catch (error) {
    console.error('Error registering IPN URL:', error.response?.data || error.message);
    res.status(500).json({ success: false, message: 'Failed to register IPN URL' });
  }
});

// Route to fetch the list of registered IPNs
app.get('/api/ipn-list', async (req, res) => {
  try {
    const token = await getPesapalAccessToken();
    const response = await axios.get(`${PESAPAL_BASE_URL}/api/URLSetup/GetIpnList`, {
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
    });
    res.json({ success: true, data: response.data });
  } catch (error) {
    console.error('Error fetching IPN list:', error.response?.data || error.message);
    res.status(500).json({ success: false, message: 'Failed to fetch IPN list' });
  }
});

// Route to submit an order request
app.post('/api/submit-order', async (req, res) => {
  const {
    id,
    currency,
    amount,
    description,
    callback_url,
    cancellation_url,
    notification_id,
    billing_address,
  } = req.body;

  try {
    const token = await getPesapalAccessToken();
    const response = await axios.post(
      `${PESAPAL_BASE_URL}/api/Transactions/SubmitOrderRequest`,
      {
        id,
        currency,
        amount,
        description,
        callback_url,
        cancellation_url,
        notification_id,
        billing_address,
      },
      {
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );
    res.json({
      success: true,
      orderTrackingId: response.data.order_tracking_id,
      merchantReference: response.data.merchant_reference,
      paymentUrl: response.data.redirect_url,
    });
  } catch (error) {
    console.error('Error submitting order request:', error.response?.data || error.message);
    res.status(500).json({ success: false, message: 'Failed to submit order request' });
  }
});

// Route to initiate Direct Mobile Money STK payment
app.post('/api/direct-stk', async (req, res) => {
  const {
    msisdn,
    payment_method,
    id,
    currency,
    amount,
    description,
    callback_url,
    notification_id,
    billing_address,
  } = req.body;

  try {
    const token = await getPesapalAccessToken();
    const response = await axios.post(
      `${PESAPAL_BASE_URL}/api/Transactions/stk`,
      {
        msisdn,
        payment_method,
        id,
        currency,
        amount,
        description,
        callback_url,
        notification_id,
        billing_address,
      },
      {
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );
    res.json({
      success: true,
      message: 'STK payment initiated successfully',
      data: response.data,
    });
  } catch (error) {
    console.error('Error initiating Direct Mobile Money STK payment:', error.response?.data || error.message);
    res.status(500).json({ success: false, message: 'Failed to initiate STK payment' });
  }
});

// Route to get transaction status
app.get('/api/transaction-status', async (req, res) => {
  const { orderTrackingId } = req.query;

  if (!orderTrackingId) {
    return res.status(400).json({ success: false, message: 'OrderTrackingId is required' });
  }

  try {
    const token = await getPesapalAccessToken();
    const response = await axios.get(
      `${PESAPAL_BASE_URL}/api/Transactions/GetTransactionStatus`,
      {
        params: { orderTrackingId },
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );
    res.json({
      success: true,
      transactionStatus: response.data,
    });
  } catch (error) {
    console.error('Error fetching transaction status:', error.response?.data || error.message);
    res.status(500).json({ success: false, message: 'Failed to fetch transaction status' });
  }
});

// Route to submit a recurring payment request
app.post('/api/submit-recurring-order', async (req, res) => {
  const {
    id,
    currency,
    amount,
    description,
    callback_url,
    cancellation_url,
    notification_id,
    billing_address,
    account_number,
    subscription_details,
  } = req.body;

  try {
    const token = await getPesapalAccessToken();
    const response = await axios.post(
      `${PESAPAL_BASE_URL}/api/Transactions/SubmitOrderRequest`,
      {
        id,
        currency,
        amount,
        description,
        callback_url,
        cancellation_url,
        notification_id,
        billing_address,
        account_number,
        subscription_details,
      },
      {
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );
    res.json({
      success: true,
      orderTrackingId: response.data.order_tracking_id,
      merchantReference: response.data.merchant_reference,
      paymentUrl: response.data.redirect_url,
    });
  } catch (error) {
    console.error('Error submitting recurring order request:', error.response?.data || error.message);
    res.status(500).json({ success: false, message: 'Failed to submit recurring order request' });
  }
});

// Route to request a refund
app.post('/api/refund-request', async (req, res) => {
  const { confirmation_code, amount, username, remarks } = req.body;

  if (!confirmation_code || !amount || !username || !remarks) {
    return res.status(400).json({ success: false, message: 'All fields are required' });
  }

  try {
    const token = await getPesapalAccessToken();
    const response = await axios.post(
      `${PESAPAL_BASE_URL}/api/Transactions/RefundRequest`,
      {
        confirmation_code,
        amount,
        username,
        remarks,
      },
      {
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );
    res.json({
      success: true,
      status: response.data.status,
      message: response.data.message,
    });
  } catch (error) {
    console.error('Error processing refund request:', error.response?.data || error.message);
    res.status(500).json({ success: false, message: 'Failed to process refund request' });
  }
});

// Route to cancel an order
app.post('/api/cancel-order', async (req, res) => {
  const { order_tracking_id } = req.body;

  if (!order_tracking_id) {
    return res.status(400).json({ success: false, message: 'OrderTrackingId is required' });
  }

  try {
    const token = await getPesapalAccessToken();
    const response = await axios.post(
      `${PESAPAL_BASE_URL}/api/Transactions/CancelOrder`,
      { order_tracking_id },
      {
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      }
    );
    res.json({
      success: true,
      status: response.data.status,
      message: response.data.message,
    });
  } catch (error) {
    console.error('Error cancelling order:', error.response?.data || error.message);
    res.status(500).json({ success: false, message: 'Failed to cancel order' });
  }
});

async function getPesapalAccessToken() {
  try {
    const response = await axios.post(
      `${PESAPAL_BASE_URL}/api/Auth/RequestToken`,
      {},
      {
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        auth: {
          username: CONSUMER_KEY,
          password: CONSUMER_SECRET,
        },
      }
    );
    console.log("Access Token:", response.data.token);
    return response.data.token;
  } catch (error) {
    console.error("Error fetching Pesapal access token:", error.response?.data || error.message);
    throw error;
  }
}

// Example usage
(async () => {
  try {
    const token = await getPesapalAccessToken();
    // Use the token for subsequent API calls
  } catch (error) {
    console.error("Failed to authenticate with Pesapal.");
  }
})();

app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
