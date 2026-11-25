"""
ocr_prescription.py - read the text from a prescription image

SRS 4.2: Python 3.11 with Tesseract OCR and Pillow.
FR-11: extract the text from an uploaded prescription image.
FR-12: give a confidence score with the extracted text.

How it is used:
    python ocr_prescription.py "C:/path/to/image.jpg" "C:/path/to/tesseract.exe"

What it prints:
    One JSON object on standard output, and nothing else. PHP reads that
    output, so anything printed here that is not JSON would break it.

    {
        "success": true,
        "text": "the text that was read",
        "lines": ["line one", "line two"],
        "confidence": 87.4,
        "word_count": 23,
        "error": null
    }

Why a separate script rather than PHP:
    Tesseract is a C++ program with no PHP binding in XAMPP. Python has a
    well-tested wrapper (pytesseract), so the OCR work happens here and PHP
    only reads the result.
"""

import json
import os
import sys


def fail(message):
    """Print a JSON failure and stop.

    Even when something goes wrong, the output stays JSON, so PHP always has
    something it can read.
    """
    print(json.dumps({
        "success": False,
        "text": "",
        "lines": [],
        "confidence": 0.0,
        "word_count": 0,
        "error": message,
    }))
    sys.exit(0)


def prepare_image(image):
    """Make the image easier for Tesseract to read.

    Three simple steps that make a real difference on photographs:
      1. greyscale, because colour does not help with text
      2. enlarge small images, since Tesseract struggles below about 300 DPI
      3. sharpen, to make the letter edges cleaner
    """
    from PIL import ImageEnhance, ImageFilter

    # 1. Colour to greyscale.
    image = image.convert("L")

    # 2. Enlarge anything smaller than 1000 pixels wide.
    width, height = image.size

    if width < 1000:
        scale = 1000 / width
        image = image.resize((int(width * scale), int(height * scale)))

    # 3. Sharpen the edges, then raise the contrast a little.
    image = image.filter(ImageFilter.SHARPEN)
    image = ImageEnhance.Contrast(image).enhance(1.5)

    return image


def read_prescription(image_path, tesseract_path):
    """Run OCR on one image and return the result as a dictionary."""
    try:
        import pytesseract
        from PIL import Image
    except ImportError as error:
        fail("Python packages are missing. Run: pip install pytesseract pillow (" + str(error) + ")")

    # On Windows, Tesseract is not on PATH, so its location is given to us.
    if tesseract_path and os.path.isfile(tesseract_path):
        pytesseract.pytesseract.tesseract_cmd = tesseract_path

    if not os.path.isfile(image_path):
        fail("The image file was not found: " + image_path)

    try:
        image = Image.open(image_path)
    except Exception as error:
        fail("The image could not be opened: " + str(error))

    try:
        image = prepare_image(image)
    except Exception:
        # Preparation is a nicety. If it fails, carry on with the original.
        image = Image.open(image_path)

    try:
        # --psm 6 tells Tesseract to treat the page as one block of text,
        # which suits a prescription better than its default guess.
        config = "--psm 6"

        text = pytesseract.image_to_string(image, config=config)

        # image_to_data gives a confidence score for every word it read.
        data = pytesseract.image_to_data(
            image,
            config=config,
            output_type=pytesseract.Output.DICT,
        )
    except Exception as error:
        message = str(error)

        if "tesseract is not installed" in message.lower() or "not in your path" in message.lower():
            fail("Tesseract OCR was not found. Check TESSERACT_PATH in .env.")

        fail("OCR failed: " + message)

    # ---------- Work out an overall confidence (FR-12) ----------
    # Tesseract gives -1 for things it did not really read, so those are left
    # out. Empty words are left out too, because they would drag the score down.
    scores = []

    for index, word in enumerate(data.get("text", [])):
        if word.strip() == "":
            continue

        try:
            score = float(data["conf"][index])
        except (ValueError, TypeError, KeyError):
            continue

        if score >= 0:
            scores.append(score)

    confidence = round(sum(scores) / len(scores), 1) if scores else 0.0

    # ---------- Tidy the text ----------
    lines = []

    for line in text.splitlines():
        cleaned = line.strip()

        # One or two stray characters are almost always noise, not medicine names.
        if len(cleaned) >= 3:
            lines.append(cleaned)

    return {
        "success": True,
        "text": "\n".join(lines),
        "lines": lines,
        "confidence": confidence,
        "word_count": len(scores),
        "error": None,
    }


def main():
    if len(sys.argv) < 2:
        fail("No image path was given.")

    image_path = sys.argv[1]
    tesseract_path = sys.argv[2] if len(sys.argv) > 2 else ""

    result = read_prescription(image_path, tesseract_path)

    # ensure_ascii keeps the output safe to move between programs.
    print(json.dumps(result, ensure_ascii=True))


if __name__ == "__main__":
    main()