/*! jscanify v1.3.3 | (c) ColonelParrot and other contributors | MIT License */
const distance = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);

export default class Jscanify {
    findPaperContour(img) {
        const gray = new cv.Mat();
        const blur = new cv.Mat();
        const threshold = new cv.Mat();
        const contours = new cv.MatVector();
        const hierarchy = new cv.Mat();
        cv.Canny(img, gray, 50, 200);
        cv.GaussianBlur(gray, blur, new cv.Size(3, 3), 0, 0, cv.BORDER_DEFAULT);
        cv.threshold(blur, threshold, 0, 255, cv.THRESH_OTSU);
        cv.findContours(threshold, contours, hierarchy, cv.RETR_CCOMP, cv.CHAIN_APPROX_SIMPLE);
        let maxArea = 0;
        let maxContour = null;
        for (let index = 0; index < contours.size(); index += 1) {
            const contour = contours.get(index);
            const area = cv.contourArea(contour);
            if (area > maxArea) {
                maxContour?.delete();
                maxContour = contour.clone();
                maxArea = area;
            }
            contour.delete();
        }
        gray.delete(); blur.delete(); threshold.delete(); contours.delete(); hierarchy.delete();
        return maxContour;
    }

    getCornerPoints(contour) {
        const center = cv.minAreaRect(contour).center;
        const corners = {};
        const distances = { topLeftCorner: 0, topRightCorner: 0, bottomLeftCorner: 0, bottomRightCorner: 0 };
        for (let index = 0; index < contour.data32S.length; index += 2) {
            const point = { x: contour.data32S[index], y: contour.data32S[index + 1] };
            const key = point.x < center.x
                ? (point.y < center.y ? 'topLeftCorner' : 'bottomLeftCorner')
                : (point.y < center.y ? 'topRightCorner' : 'bottomRightCorner');
            const value = distance(point, center);
            if (value > distances[key]) { corners[key] = point; distances[key] = value; }
        }
        return corners;
    }

    extractPaper(image, width, height) {
        const source = cv.imread(image);
        const contour = this.findPaperContour(source);
        if (!contour) { source.delete(); return null; }
        const corners = this.getCornerPoints(contour);
        contour.delete();
        if (Object.keys(corners).length !== 4) { source.delete(); return null; }
        const destination = new cv.Mat();
        const sourcePoints = cv.matFromArray(4, 1, cv.CV_32FC2, [
            corners.topLeftCorner.x, corners.topLeftCorner.y,
            corners.topRightCorner.x, corners.topRightCorner.y,
            corners.bottomLeftCorner.x, corners.bottomLeftCorner.y,
            corners.bottomRightCorner.x, corners.bottomRightCorner.y,
        ]);
        const destinationPoints = cv.matFromArray(4, 1, cv.CV_32FC2, [0, 0, width, 0, 0, height, width, height]);
        const transform = cv.getPerspectiveTransform(sourcePoints, destinationPoints);
        cv.warpPerspective(source, destination, transform, new cv.Size(width, height), cv.INTER_LINEAR, cv.BORDER_CONSTANT, new cv.Scalar());
        const canvas = document.createElement('canvas');
        cv.imshow(canvas, destination);
        source.delete(); destination.delete(); sourcePoints.delete(); destinationPoints.delete(); transform.delete();
        return canvas;
    }
}
