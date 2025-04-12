const sgMail = require('@sendgrid/mail');

// Set your SendGrid API key
sgMail.setApiKey('YOUR_SENDGRID_API_KEY');

async function sendVerificationEmail(email, token) {
    const verificationLink = `https://yourdomain.com/verify?email=${encodeURIComponent(email)}&token=${token}`;
    const msg = {
        to: email,
        from: 'no-reply@digitalnexify.com', // Your verified sender email
        subject: 'Verify Your Email Address',
        html: `
            <p>Thank you for signing up for Digital Nexify!</p>
            <p>Please verify your email address by clicking the link below:</p>
            <a href="${verificationLink}">Verify Email</a>
            <p>If you did not sign up, you can safely ignore this email.</p>
        `,
    };

    try {
        await sgMail.send(msg);
        console.log('Verification email sent to:', email);
    } catch (error) {
        console.error('Error sending email:', error);
    }
}

module.exports = sendVerificationEmail;
