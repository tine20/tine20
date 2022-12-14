FROM node:12.22-alpine as jsbuild

RUN export E=1
RUN echo $E