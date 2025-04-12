const nodemailer = require('nodemailer');

const transporter = nodemailer.createTransport({
    host: 'sandbox.smtp.mailtrap.io',
    port: 2525,
    auth: {
        user: 'a1bf3fa9d95cd5',
        pass: 'c789f19cfe3450'
    }
});

async function sendVerificationEmail(email, token) {
    try {
        const info = await transporter.sendMail({
            from: '"Digital Nexify" <no-reply@digitalnexify.com>',
            to: email,
            subject: 'Verify Your Email',
            text: `Please verify your email by clicking the following link: https://your-website.com/verify?token=${token}`,
            html: `<p>Please verify your email by clicking the following link:</p><a href="https://your-website.com/verify?token=${token}">Verify Email</a>`
        });

        console.log('Verification email sent: %s', info.messageId);
    } catch (error) {
        console.error('Error sending verification email:', error);
    }
}

module.exports = sendVerificationEmail;
