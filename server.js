const express = require('express');
const path = require('path');
const PesaPal = require('pesapal'); // Import the PesaPal library
const bcrypt = require('bcrypt');
const validator = require('validator');

const app = express();
const PORT = process.env.PORT || 3000;

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

app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
